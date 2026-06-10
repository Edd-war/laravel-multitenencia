<?php

namespace Eddwar\Multitenencia\Actions;

use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Eddwar\Multitenencia\Concerns\VincularComoInquilinoActual;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Exceptions\ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola;
use Eddwar\Multitenencia\Jobs\InquilinoNoReconocido;
use Eddwar\Multitenencia\Jobs\InquilinoReconocido;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Context;
use ReflectionClass;
use Throwable;

class AccionHacerColaInquilinoReconocido
{
    use UtilizaConfiguracionMultitenencia;
    use VincularComoInquilinoActual;

    public function execute(): void
    {
        $this
            ->escucharTrabajosEnProcesamiento()
            ->escucharTrabajosEnReintentoSolicitado();
    }

    protected function escucharTrabajosEnProcesamiento(): static
    {
        app('events')->listen(JobProcessing::class, function (JobProcessing $event) {
            $this->vincularUolvidarInquilinoActual($event);
        });

        return $this;
    }

    protected function escucharTrabajosEnReintentoSolicitado(): static
    {
        app('events')->listen(JobRetryRequested::class, function (JobRetryRequested $event) {
            $this->vincularUolvidarInquilinoActual($event);
        });

        return $this;
    }

    protected function esInquilinoReconocido(JobProcessing|JobRetryRequested $event): bool
    {
        $payload = $this->obtenerCargaDelEvento($event);

        $serializedCommand = $payload['data']['command'];

        if (! str_starts_with($serializedCommand, 'O:')) {
            $serializedCommand = app(Encrypter::class)->decrypt($serializedCommand);
        }

        try {
            $command = unserialize($serializedCommand);
        } catch (Throwable) {
            /**
             * We might need the tenant to unserialize jobs as models could
             * have global scopes set that require a current tenant to
             * be active. vincularUolvidarInquilinoActual wil reset it.
             */
            $tenantId = Context::get($this->claveDeContextoDelinquilinoActual());
            if (! $tenantId) {
                $tenantId = $payload['context'][$this->claveDeContextoDelinquilinoActual()] ?? null;
            }
            if (! $tenantId) {
                $tenantId = $payload['illuminate:log:context']['data'][$this->claveDeContextoDelinquilinoActual()] ?? null;
            }
            if ($tenantId) {
                if (is_string($tenantId) && (str_starts_with($tenantId, 'i:') || str_starts_with($tenantId, 's:'))) {
                    try {
                        $unserialized = @unserialize($tenantId);
                        if ($unserialized !== false) {
                            $tenantId = $unserialized;
                        }
                    } catch (Throwable) {
                        // Ignore
                    }
                }
                /** @var Model $tenantModel */
                $tenantModel = app(EsInquilino::class);
                $tenant = $tenantModel->newQuery()->find($tenantId);
                if ($tenant instanceof EsInquilino) {
                    $tenant->hacerActual();
                }
            }

            $command = unserialize($serializedCommand);
        }

        $job = $this->obtenerTrabajoDeLaCola($command);

        $reflection = new ReflectionClass($job);

        if ($reflection->implementsInterface(config('multitenencia.interfaz_reconoce_inquilinos', InquilinoReconocido::class))) {
            return true;
        }

        if ($reflection->implementsInterface(config('multitenencia.interfaz_no_reconoce_inquilinos', InquilinoNoReconocido::class))) {
            return false;
        }

        if (in_array($reflection->name, config('multitenencia.trabajos_que_reconocen_inquilinos'))) {
            return true;
        }

        if (in_array($reflection->name, config('multitenencia.trabajos_que_no_reconocen_inquilinos'))) {
            return false;
        }

        return config('multitenencia.colas_reconocen_inquilinos_por_defecto') === true;
    }

    protected function obtenerTrabajoDeLaCola(object $queueable)
    {
        $job = Arr::get(config('multitenencia.cola_a_trabajo'), $queueable::class);

        if (! $job) {
            return $queueable;
        }

        if (method_exists($queueable, $job)) {
            return $queueable->{$job}();
        }

        return $queueable->$job;
    }

    protected function obtenerCargaDelEvento($event): ?array
    {
        return match (true) {
            $event instanceof JobProcessing => $event->job->payload(),
            $event instanceof JobRetryRequested => $event->payload(),
            default => null,
        };
    }

    protected function encontrarInquilino(JobProcessing|JobRetryRequested $event): EsInquilino
    {
        $tenantId = Context::get($this->claveDeContextoDelinquilinoActual());

        if (! $tenantId) {
            $payload = $this->obtenerCargaDelEvento($event);
            $tenantId = $payload['context'][$this->claveDeContextoDelinquilinoActual()] ?? null;
            if (! $tenantId) {
                $tenantId = $payload['illuminate:log:context']['data'][$this->claveDeContextoDelinquilinoActual()] ?? null;
            }
        }

        if ($tenantId) {
            if (is_string($tenantId) && (str_starts_with($tenantId, 'i:') || str_starts_with($tenantId, 's:'))) {
                try {
                    $unserialized = @unserialize($tenantId);
                    if ($unserialized !== false) {
                        $tenantId = $unserialized;
                    }
                } catch (Throwable) {
                    // Ignore
                }
            }
        }

        if (! $tenantId) {
            if ($event instanceof JobProcessing) {
                $event->job->delete();
            }

            throw ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola::noIdSet($event);
        }

        /** @var Model $tenantModel */
        $tenantModel = app(EsInquilino::class);
        $tenant = $tenantModel->newQuery()->find($tenantId);

        if (! $tenant instanceof EsInquilino) {
            if ($event instanceof JobProcessing) {
                $event->job->delete();
            }

            throw ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola::inquilinoNoEncontrado($event);
        }

        return $tenant;
    }

    protected function vincularUolvidarInquilinoActual(JobProcessing|JobRetryRequested $event): void
    {
        if ($this->esInquilinoReconocido($event)) {
            $tenant = $this->encontrarInquilino($event);

            $tenant->hacerActual();

            $this->vincularComoInquilinoActual($tenant);

            return;
        }

        app(EsInquilino::class)::olvidarActual();
    }
}
