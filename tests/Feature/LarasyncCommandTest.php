<?php

it('runs successfully and outputs all done', function () {
    $this->artisan('larasync')
        ->expectsOutputToContain('All done')
        ->assertSuccessful();
});
