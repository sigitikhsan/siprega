<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ConvertGpsCapturedAtToApplicationTimezone extends Migration
{
    public function up()
    {
        $this->convert('UTC', config('app.timezone', 'Asia/Jakarta'));
    }

    public function down()
    {
        $this->convert(config('app.timezone', 'Asia/Jakarta'), 'UTC');
    }

    private function convert(string $fromTimezone, string $toTimezone): void
    {
        DB::table('attendances')
            ->whereNotNull('check_in_captured_at')
            ->orWhereNotNull('check_out_captured_at')
            ->orderBy('id')
            ->chunkById(200, function ($attendances) use ($fromTimezone, $toTimezone) {
                foreach ($attendances as $attendance) {
                    $values = [];
                    foreach (['check_in_captured_at', 'check_out_captured_at'] as $column) {
                        if ($attendance->{$column}) {
                            $values[$column] = Carbon::parse($attendance->{$column}, $fromTimezone)
                                ->setTimezone($toTimezone)
                                ->format('Y-m-d H:i:s');
                        }
                    }
                    if ($values) DB::table('attendances')->where('id', $attendance->id)->update($values);
                }
            });
    }
}
