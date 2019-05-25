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

It will auto create a new file at ```/app/CBModels/Books.php``` with the following file structure : 

```php
<?php
namespace App\Models;

use DB;
use Crocodicstudio\Cbmodel\Core\Model;

class Books extends Model
{
    public static $tableName = "books";

    private $id;
    private $createdAt;
    private $name;

    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
    
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
}
```

### 2. Using CB Model class on your Controller
Insert ```use App\CBModels\Books; ``` at top of your controller class name.

```php
<?php 
namespace App\Http\Controllers;

use App\Models\Books;

class FooController extends Controller {
    
    public function index() 
    {
        $books = Books::all();
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
        
        return redirect()->back()->with(["message"=>"Book ".$book->getName()." has been deleted!"]);
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

I assume that you have create a ```categories``` model, so make sure that now we have two files in the ```/app/CBModels/```
``` 
/Books.php
/Categories.php
```
Now we go back to the controller 
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
        $data['book_id'] = $book->getId();
        $data['book_name'] = $book->getName();
        $data['book_category_id'] = $book->getCategories()->getId();
        $data['book_category_name'] = $book->getCategories()->getName();
        
        return view("book_detail",$data);
    }
    
    ...
}
?>
```
As you can see now we can get the category name by using ```->getCategories()->getName()``` without any SQL Query or even Database Builder syntax. Also you can recursively go down to your relation with NO LIMIT.

### 4. How to Casting DB Builder Collection output to CB Model Class?
You can easily cast your simple database builder collection to cb model class. Make sure that the database builder have no any join/relation operation. And only support from simple table query

```php 
$row = DB::table("books")->where("id",1)->first();

//Cast to CBModel
$model = new Books($row);

//And then you can use cb model normally
echo $model->getName();
```

### 5. How to insert the data with CB Model
You can easily insert the data with method ```->save()``` like bellow:
```php 
$book = new Books();
$book->setCreatedAt(date("Y-m-d H:i:s")); //this createdAt is a magic method you can ignore this
$book->setName("New Book");
$book->setCategories(1);
$book->save();
```
Then if you want to get the last insert id you can do like bellow:
```php
...
$book->save();
$lastInsertId = $book->getId();
...
```

### 5. How to update the data with CB Model
You can easily update the data, just find it for first : 
```php 
$book = Books::findById(1);
$book->setName("New Book");
$book->setCategories(1);
$book->save();
```
or 
```php 
$book = new Books();
$book->setId(1);
$book->setName("New Book");
$book->setCategories(1);
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
Books::delete(1);
```
or
```php 
Books::deleteById(1);
```
