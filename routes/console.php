<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar actualización de estados de crédito en mora (diario)
Schedule::call(function () {
    \App\Models\Venta::where('es_credito', true)
        ->where('estado_credito', \App\Models\Venta::ESTADO_PARCIAL)
        ->where('fecha_vencimiento', '<', now())
        ->update(['estado_credito' => \App\Models\Venta::ESTADO_MORA]);
})->daily()->name('actualizar-mora')->description('Actualizar créditos en mora');
