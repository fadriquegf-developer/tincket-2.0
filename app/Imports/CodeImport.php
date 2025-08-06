<?php

namespace App\Imports;

use App\Models\Censu;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class CodeImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    use SkipsFailures;

    private $brand_id;

    public function __construct($brand_id)
    {
        $this->brand_id = $brand_id;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Censu([
            'brand_id' => $this->brand_id,
            'name' => $row['name'] ?? '',
            'code' => $row['code'],
        ]);
    }

    public function rules(): array
    {
        return ['*.code' => 'required'];
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
