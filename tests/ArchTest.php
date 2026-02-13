<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('services are in the Services namespace')
    ->expect('MrBohem\Larasync\Services')
    ->toBeClasses();

arch('commands extend Illuminate Command')
    ->expect('MrBohem\Larasync\Commands')
    ->toExtend('Illuminate\Console\Command');

arch('support classes are in the Support namespace')
    ->expect('MrBohem\Larasync\Support')
    ->toBeClasses();

arch('facades extend Illuminate Facade')
    ->expect('MrBohem\Larasync\Facades')
    ->toExtend('Illuminate\Support\Facades\Facade');

arch('livewire components extend Livewire Component')
    ->expect('MrBohem\Larasync\Http\Livewire')
    ->toExtend('Livewire\Component');
