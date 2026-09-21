<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->assertSafeTestDatabase($app);

        return $app;
    }

    /**
     * Prevent PHPUnit or Dusk from writing to demo/production by mistake.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function assertSafeTestDatabase($app)
    {
        $connection = (string) $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");
        $databaseName = strtolower(basename(str_replace('\\', '/', $database)));
        $isDuskTest = $this instanceof \Tests\DuskTestCase;
        $expectedSuffix = $isDuskTest ? '_dusk' : '_testing';

        if ($databaseName === '' || substr($databaseName, -strlen($expectedSuffix)) !== $expectedSuffix) {
            throw new \RuntimeException(sprintf(
                'Pengujian dibatalkan: database harus berakhiran "%s", tetapi koneksi "%s" mengarah ke "%s".',
                $expectedSuffix,
                $connection,
                $databaseName === '' ? '(kosong)' : $databaseName
            ));
        }
    }
}
