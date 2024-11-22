<?php


 

arch('app')
    ->expect('Namu\\WireChat')
    //->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);