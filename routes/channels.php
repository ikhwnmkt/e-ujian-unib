<?php

use App\Models\Test;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private Channel untuk Live Monitoring Ujian Essai
// Channel: exam.{testId}
// Otorisasi: Admin atau Dosen pemilik ujian
Broadcast::channel('exam.{testId}', function ($user, $testId) {
    if ($user->role === 'admin') {
        return true;
    }

    if ($user->role === 'dosen') {
        return Test::where('id', $testId)
            ->where('dosen_id', $user->id)
            ->exists();
    }

    return false;
});
