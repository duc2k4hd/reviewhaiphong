<?php
use App\Models\Category;

foreach(Category::all() as $c) {
    echo $c->id . ' - ' . $c->name . ' - Parent: ' . ($c->parent_id ?? 'NULL') . ' - Status: ' . $c->status . "\n";
}
