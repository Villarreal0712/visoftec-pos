<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\StreamedResponse;

trait HandlesTableFeatures
{
    /**
     * Retorna el número de registros por página validado.
     */
    protected function getPerPage(int $default = 25): int
    {
        $value = (int) request('per_page', $default);
        return in_array($value, [10, 25, 50, 100]) ? $value : $default;
    }

    /**
     * Genera y descarga un CSV con BOM UTF-8 (compatible con Excel en español).
     *
     * @param  string    $filename  Nombre base del archivo (sin extensión)
     * @param  iterable  $items     Colección de registros
     * @param  array     $columnas  ['Encabezado' => fn($item) => valor]
     */
    protected function exportarCsv(string $filename, iterable $items, array $columnas): StreamedResponse
    {
        $nombreArchivo = $filename . '_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'              => 'text/csv; charset=UTF-8',
            'Content-Disposition'       => "attachment; filename=\"{$nombreArchivo}\"",
            'Cache-Control'             => 'no-cache, no-store, must-revalidate',
            'Pragma'                    => 'no-cache',
            'Expires'                   => '0',
        ];

        return response()->stream(function () use ($items, $columnas) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para que Excel abra correctamente tildes y ñ
            fwrite($handle, "\xEF\xBB\xBF");

            // Fila de encabezados
            fputcsv($handle, array_keys($columnas), ';');

            // Filas de datos
            foreach ($items as $item) {
                $fila = array_map(fn($cb) => $cb($item), $columnas);
                fputcsv($handle, $fila, ';');
            }

            fclose($handle);
        }, 200, $headers);
    }
}
