<?php

use Illuminate\Foundation\Inspiring;

Schedule::command('sanctum:prune-expired --hours=24')->daily();
