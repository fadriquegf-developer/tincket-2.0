<?php

namespace App\Imports;

use App\Models\SessionCode;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class SessionCodeImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    private $session_id;

    public function  __construct($session_id)
    {
        $this->session_id = $session_id;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new SessionCode([
            'session_id' => $this->session_id,
            'name' => $row['name'],
            'code' => $row['code'],
        ]);
    }

    public function chunkSize(): int
    {
        return 1000; // Aumenta el chunkSize si el servidor lo permite
    }

    public function batchSize(): int
    {
        return 1000; // Aumenta el batchSize si el servidor lo permite
    }
}
