# CB Laravel Model Repository
An alternative about laravel eloquent

### Requirement
Laravel 5.* | 6.* | 7.*

### Install Command
``composer require crocodicstudio/cbmodel=^2.0``

### 1. Create a model

*Create a model from a table*<br/>
``php artisan create:model foo_bar_table``

*Create model for all tables*<br/>
``php artisan create:model``

*Create a model with other connection*<br/>
``php artisan create:model foo_bar_table --connection=con2``

I assume that you have a ```books``` table with the structure like bellow:
```
id (Integer) Primary Key
created_at (Timestamp)
name (Varchar) 255
```

It will auto create a new file at ```/app/Models/Books.php``` with the following file structure : 

```php
<?php
namespace App\Models;

use DB;
use Crocodicstudio\Cbmodel\Core\Model;

class Books extends Model
{
    public $tableName = "books";
    public $connection = "mysql";
    public $primary_key = "id";

    public $id;
    public $created_at;
    public $name;
}
```

### 2. Using CB Model class on your Controller
Insert ```use App\Models\Books; ``` at top of your controller class name.

```php
<?php 
namespace App\Http\Controllers;

use App\Models\Books;

class FooController extends Controller {
    
    public function index() 
    {
        $books = Books::latest();
        return view("books", ["bookData"=>$books]);
    }
    
    public function detail($id)
    {
        $book = Books::find($id);
        return view("book_detail", ["book"=>$book]);
    }
    
    public function delete($id)
    {
        Books::deleteById($id);
        
        return redirect()->back()->with(["message"=>"Book ".$book->name." has been deleted!"]);
    }
}
?>
```

### 3. Using CB Model class that has a relation
I assume you have a table ```categories``` for book relation like bellow : 
```
id (Integer) Primary Key
name (Varchar) 255
```
and your book structure to be like bellow:
```
id (Integer) Primary Key
created_at (Timestamp)
categories_id (Integer)
name (Varchar) 255
```
Now you have to create a model for ```categories``` table, you can following previous steps.

I assume that you have create a ```categories``` model, so make sure that now we have two files in the ```/app/Models/```
``` 
/Books.php
/Categories.php
```
Open the Books model , and add this bellow method
```php
    /**
    * @return Categories
    */
    public function category() {
        return $this->belongsTo("App\Models\Categories");
    }

    // or 
    /**
    * @return Categories
    */
    public function category() {
        return Categories::find($this->categories_id);
    }
```
Then open the FooController 
```php
<?php 
namespace App\Http\Controllers;

use App\Models\Books;

class FooController extends Controller {
    
    ...
    
    public function detail($id)
    {
        $book = Books::find($id);
        
        $data = [];
        $data['book_id'] = $book->id;
        $data['book_name'] = $book->name;
        $data['book_category_id'] = $book->category()->id;
        $data['book_category_name'] = $book->category()->name;
        
        return view("book_detail",$data);
    }
    
}
?>
```
As you can see now we can get the category name by using ```->category()->name``` without any SQL Query or even Database Builder syntax. Also you can recursively go down to your relation with NO LIMIT.

### 4. How to Casting DB Builder Collection output to CB Model Class?
You can easily cast your simple database builder collection to cb model class.

```php 
$row = DB::table("books")->where("id",1)->first();

//Cast to CB Model
$model = new Books($row);

//And then you can use cb model normally
echo $model->name;
```

### 5. How to insert the data with CB Model
You can easily insert the data with method ```->save()``` like bellow:
```php 
$book = new Books();
$book->created_at = date("Y-m-d H:i:s"); //this created_at is a magic method you can ignore this
$book->name = "New Book";
$book->categories_id = 1;
$book->save();
```
Then if you want to get the last insert id you can do like bellow:
```php
...
$book->save();
$lastInsertId = $book->id; // get the id from id property
...
```

### 5. How to update the data with CB Model
You can easily update the data, just find it for first : 
```php 
$book = Books::findById(1);
$book->name = "New Book";
$book->categories_id = 1;
$book->save();
```
### 5. How to delete the data?
You can easily delete the data, just find it for first : 
```php 
$book = Books::findById(1);
$book->delete();
```
or
```php 
Books::deleteById(1);
```

## Model Method Available
```php
/**
* Find all data by specific condition.
*/ 
$result = FooBar::findAllBy($column, $value = null, $sorting_column = "id", $sorting_dir = "desc");
// or 
$result = FooBar::findAllBy(['foo'=>1,'bar'=>2]);

/**
* Find all data without sorting
*/
$result = FooBar::findAll();

/**
* Count the records of table
*/ 
$result = FooBar::count();

/**
* Count the records with specific condition 
*/
$result = FooBar::countBy($column, $value = null);
// or
$result = FooBar::countBy(['foo'=>1,'bar'=>2]);

/**
* Find all datas and ordering the data to descending
*/
$result = FooBar::findAllDesc($column = "id");
// or simply
$result = FooBar::latest();

/**
* Find all datas and ordering the data to ascending
*/
$result = FooBar::findAllAsc($column = "id");
// or simply
$result = FooBar::oldest();

/** 
* Find/Fetch a record by a primary key value
*/
$result = FooBar::findById($id);
// or simply
$result = FooBar::find($id);

/**
* Create a custom query, and result laravel Query Builder collection
*/
$result = FooBar::table()->where("foo",1)->first();

/**
* Create a custom query and casting to model object
*/
$result = FooBar::query(function($query) {
    return $query->where("bar",1);
});

/**
* Create a custom list query and casting them to model objects
*/
$result = FooBar::queryList(function($query) {
    return $query->where("bar",1);
});


/**
* Find a record by a specific condition
*/
$result = Foobar::findBy($column, $value = null);
// or 
$result = Foobar::findBy(['foo'=>1,'bar'=>2]);

/**
* To run the insert SQL Query
*/
$fooBar = new FooBar();
$fooBar->name = "Lorem ipsum";
$fooBar->save();

/**
* To bulk insert
*/
$data = [];
$foo = new FooBar();
$foo->name = "Lorem ipsum 1";
array_push($data, $foo);
$bar = new FooBar();
$bar->name = "Lorem ipsum 2";
array_push($data, $bar);
FooBar::bulkInsert($data);


/**
* To run the update SQL Query
*/
$fooBar = FooBar::findById($value);
$fooBar->name = "Lorem ipsum";
$fooBar->save();

/**
* To delete the record by a primary key value
*/
FooBar::deleteById($value);

/**
* To delete the record by a specific condition
*/
FooBar::deleteBy($column, $value = null);
// or
Foobar::deleteBy(['foo'=>1,'bar'=>2]);

/**
* To delete after you fetch the record 
*/
$fooBar = FooBar::findById($value);
$fooBar->delete();
```

## A One-To-Many Relationship
```php
class Posts extends Model {
    // etc
    
    /**
    * @return App\Models\Comments[]
    */
    public function comments() {
        return $this->hasMany(Comments::class);
    }
    
    // or with full option
    /**
    * @return App\Models\Comments[]
    */
    public function comments() {
        return $this->hasMany(Comments::class, "foreign_key", "local_key", function($condition) {
            return $condition->where("status","Active");
        });
    }
}
```

## A One-To-One Relationship
```php
class Comments extends Model {
    // etc
    
    /**
    * @return App\Models\Posts
    */
    public function post() {
        return $this->belongsTo(Posts::class);
    }
    
    // or with full option
    /**
    * @return App\Models\Posts
    */
    public function post() {
        return $this->belongsTo(Posts::class, "foreign_key", "local_key");
    }
}
```

## Other Useful
1. [CRUDBooster Laravel CRUD Generator](https://github.com/crocodic-studio/crudbooster)
