<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NaicsCode;
use App\Models\NaicsIndex;
use Illuminate\Support\Facades\DB;

class ImportNaicsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'naics:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
   public function handle()
{
    $this->info('Starting import...');

    DB::beginTransaction();

    try {

        $allNaics = json_decode(
            file_get_contents(storage_path('app/data/all-naics.json')),
            true
        );

        foreach ($allNaics as $code => $description) {

            NaicsCode::updateOrCreate(
                ['naics_code' => $code],
                ['description' => trim($description)]
            );
        }

        $this->info('NAICS Codes Imported');

        $indexData = json_decode(
            file_get_contents(storage_path('app/data/naics_index_data.json')),
            true
        );

        foreach ($indexData as $row) {

            $naicsCode = NaicsCode::where(
                'naics_code',
                $row['NAICS22']
            )->first();

            if (!$naicsCode) {
                continue;
            }

            NaicsIndex::create([
                'naics_code_id' => $naicsCode->id,
                'index_description' => $row['INDEX ITEM DESCRIPTION']
            ]);
        }

        DB::commit();

        $this->info('Index Data Imported Successfully');

    } catch (\Exception $e) {

        DB::rollBack();

        $this->error($e->getMessage());
    }
}
}

