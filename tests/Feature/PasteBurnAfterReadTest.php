<?php

use App\Models\Paste;

it('deletes a burn-after-read paste after its second web visit', function () {
    $paste = Paste::create([
        'key' => str_repeat('a', 32),
        'content' => 'single-use paste',
        'burn_after_read' => true,
        'content_hash' => hash('sha256', 'single-use paste'),
    ]);

    $this->get('/'.$paste->key)->assertOk();
    $this->assertDatabaseHas('pastes', ['id' => $paste->id, 'read_count' => 1]);

    $this->get('/'.$paste->key)->assertOk();
    $this->assertDatabaseMissing('pastes', ['id' => $paste->id]);
});

it('deletes a burn-after-read paste after its second API visit', function () {
    $paste = Paste::create([
        'key' => str_repeat('b', 32),
        'content' => 'single-use API paste',
        'burn_after_read' => true,
        'content_hash' => hash('sha256', 'single-use API paste'),
    ]);

    $this->getJson('/api/documents/'.$paste->key)
        ->assertOk()
        ->assertExactJson([
            'data' => 'single-use API paste',
            'key' => $paste->key,
        ]);
    $this->assertDatabaseHas('pastes', ['id' => $paste->id, 'read_count' => 1]);

    $this->getJson('/api/documents/'.$paste->key)
        ->assertOk()
        ->assertExactJson([
            'data' => 'single-use API paste',
            'key' => $paste->key,
        ]);

    $this->assertDatabaseMissing('pastes', ['id' => $paste->id]);
});
