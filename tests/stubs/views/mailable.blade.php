@if (\Eddwar\Multitenencia\Models\Inquilino::comprobarActual())
    Current tenant ID: {{ \Eddwar\Multitenencia\Models\Inquilino::actual()->id }}
@else
    This is the propietario
@endif
