# CB Laravel Model Repository
An alternative about laravel eloquent

### Install Command
``composer require crocodicstudio/cbmodel``

### 1. Create a model from existing table
``php artisan make:cbmodel --table={the table name}``

If you want to generate All your table you can ignore the <code>--table</code> option.

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
    public static $tableName = "books";
    public static $connection = "mysql";
    public static $primary_key = "id";

    public $id;
    public $createdAt;
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
        $books = Books::findAllDesc();
        return view("books", ["bookData"=>$books]);
    }
    
    public function detail($id)
    {
        $book = Books::findById($id);
        return view("book_detail", ["book"=>$book]);
    }
    
    public function delete($id)
    {
        $book = Books::findById($id);
        $book->delete();
        
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
        return Categories::findById($this->categories_id);
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
        $book = Books::findById($id);
        
        $data = [];
        $data['book_id'] = $book->id;
        $data['book_name'] = $book->name;
        $data['book_category_id'] = $book->category()->id;
        $data['book_category_name'] = $book->category()->name;
        
        return view("book_detail",$data);
    }
    
    ...
}
?>
```
As you can see now we can get the category name by using ```->category()->name``` without any SQL Query or even Database Builder syntax. Also you can recursively go down to your relation with NO LIMIT.

### 4. How to Casting DB Builder Collection output to CB Model Class?
You can easily cast your simple database builder collection to cb model class. Make sure that the database builder have no any join/relation operation. And only support from simple table query

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
