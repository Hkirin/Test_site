<?php

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\Schema;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Category::truncate();
        $categories = ["fashion", "make-up", "ict", "furniture"];
        foreach($categories as $category){
            factory(Category::class)->create();
        }
        Schema::enableForeignKeyConstraints();
    }
}
