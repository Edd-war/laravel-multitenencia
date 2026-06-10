<?php

use Eddwar\Multitenencia\Tasks\ColeccionDeTareas;
use Eddwar\Multitenencia\Tests\Feature\Tareas\TestClasses\TareaFicticia;

it('will instantiate all class names', function () {
    $coleccionDeTareas = new ColeccionDeTareas([TareaFicticia::class]);

    expect($coleccionDeTareas->first())->toBeInstanceOf(TareaFicticia::class);
});

it('can pass parameters to the tasks', function () {
    $coleccionDeTareas = new ColeccionDeTareas([
        TareaFicticia::class => ['a' => 1, 'b' => 2],
    ]);

    $task = $coleccionDeTareas->first();

    expect($task->a)->toEqual(1)
        ->and($task->b)->toEqual(2);
});

it('can handle duplicate tasks with other parameters', function () {
    $coleccionDeTareas = new ColeccionDeTareas([
        [TareaFicticia::class => ['a' => 1, 'b' => 2]],
        [TareaFicticia::class => ['a' => 3, 'b' => 4]],
    ]);

    expect($coleccionDeTareas[0]->a)->toEqual(1)
        ->and($coleccionDeTareas[0]->b)->toEqual(2)
        ->and($coleccionDeTareas[1]->a)->toEqual(3)
        ->and($coleccionDeTareas[1]->b)->toEqual(4);
});
