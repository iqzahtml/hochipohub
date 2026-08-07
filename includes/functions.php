<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Global Functions
|--------------------------------------------------------------------------
|
| Used by:
| - customer
| - seller
| - admin
| - ajax
| - auth
|
|--------------------------------------------------------------------------
*/


require_once dirname(__DIR__) . '/config.php';

require_once dirname(__DIR__) . '/database/db.php';



/*
|--------------------------------------------------------------------------
| Security / Clean Input
|--------------------------------------------------------------------------
*/


function clean($data)

{

    return htmlspecialchars(

        trim($data),

        ENT_QUOTES,

        'UTF-8'

    );

}




function escape($data)

{

    global $conn;


    return $conn->real_escape_string(

        trim($data)

    );

}



/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/


function redirect($url)

{

    header(

        "Location: ".$url

    );

    exit();

}



/*
|--------------------------------------------------------------------------
| Flash Message
|--------------------------------------------------------------------------
*/


function setFlash($type,$message)

{

    $_SESSION['flash'] = [

        'type'=>$type,

        'message'=>$message

    ];

}




function getFlash()

{

    if(isset($_SESSION['flash'])){


        $flash = $_SESSION['flash'];


        unset($_SESSION['flash']);


        return $flash;


    }


    return null;

}



/*
|--------------------------------------------------------------------------
| User Role Checking
|--------------------------------------------------------------------------
*/


function isCustomer()

{

    return isset($_SESSION['role'])

        && $_SESSION['role']=='customer';

}



function isSeller()

{

    return isset($_SESSION['role'])

        && $_SESSION['role']=='vendor';

}



function isAdmin()

{

    return isset($_SESSION['role'])

        && $_SESSION['role']=='admin';

}



/*
|--------------------------------------------------------------------------
| User Information
|--------------------------------------------------------------------------
*/


function userID()

{

    return $_SESSION['user_id'] ?? null;

}



function userName()

{

    return $_SESSION['user_name'] ?? '';

}



function userRole()

{

    return $_SESSION['role'] ?? '';

}



/*
|--------------------------------------------------------------------------
| Cart Function
|--------------------------------------------------------------------------
*/


function getCartCount($user_id)

{

    global $conn;


    $sql = "

    SELECT SUM(quantity) AS total

    FROM cart

    WHERE customer_id='$user_id'

    ";



    $result = $conn->query($sql);



    if($result){


        $row=$result->fetch_assoc();


        return $row['total'] ?? 0;


    }


    return 0;


}



/*
|--------------------------------------------------------------------------
| Wishlist Function
|--------------------------------------------------------------------------
*/


function getWishlistCount($user_id)

{

    global $conn;


    $sql = "

    SELECT COUNT(*) AS total

    FROM wishlist

    WHERE user_id='$user_id'

    ";



    $result=$conn->query($sql);



    if($result){


        $row=$result->fetch_assoc();


        return $row['total'];


    }


    return 0;


}



/*
|--------------------------------------------------------------------------
| Product Image
|--------------------------------------------------------------------------
*/


function productImage($image)

{


    if(!empty($image)){


        return PRODUCT_UPLOAD_URL.$image;


    }


    return IMAGE_URL.DEFAULT_PRODUCT_IMAGE;


}



/*
|--------------------------------------------------------------------------
| Vendor Image
|--------------------------------------------------------------------------
*/


function vendorImage($image)

{


    if(!empty($image)){


        return VENDOR_UPLOAD_URL.$image;


    }


    return IMAGE_URL."logo.jpg";


}



/*
|--------------------------------------------------------------------------
| Price Format
|--------------------------------------------------------------------------
*/


function price($amount)

{

    return CURRENCY." "

    .number_format(

        $amount,

        2

    );


}



/*
|--------------------------------------------------------------------------
| Generate OTP
|--------------------------------------------------------------------------
*/


function generateOTP()

{

    return rand(

        100000,

        999999

    );

}



/*
|--------------------------------------------------------------------------
| Generate Random Code
|--------------------------------------------------------------------------
*/


function generateCode($length=6)

{

    $characters =

    '0123456789';



    $code='';



    for($i=0;$i<$length;$i++){


        $code .=

        $characters[rand(0,strlen($characters)-1)];


    }


    return $code;


}



/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/


function requireLogin()

{

    if(!isset($_SESSION['user_id'])){


        setFlash(

            'error',

            'Please login first.'

        );


        redirect(

            BASE_URL.'auth/login.php'

        );


    }


}



/*
|--------------------------------------------------------------------------
| Check Seller
|--------------------------------------------------------------------------
*/


function requireSeller()

{

    requireLogin();



    if(!isSeller()){


        redirect(

            BASE_URL.'index.php'

        );


    }


}



/*
|--------------------------------------------------------------------------
| Check Admin
|--------------------------------------------------------------------------
*/


function requireAdmin()

{

    requireLogin();



    if(!isAdmin()){


        redirect(

            BASE_URL.'index.php'

        );


    }


}



/*
|--------------------------------------------------------------------------
| Upload Image
|--------------------------------------------------------------------------
*/


function uploadImage(

    $file,

    $folder

)


{


    if(!isset($file)

        || $file['error'] !=0)

    {


        return false;


    }



    $extension =

    strtolower(

        pathinfo(

            $file['name'],

            PATHINFO_EXTENSION

        )

    );



    $allowed=[

        'jpg',

        'jpeg',

        'png',

        'webp'

    ];



    if(!in_array($extension,$allowed)){


        return false;


    }



    $filename =

    time()

    .'_'

    .uniqid()

    .'.'

    .$extension;



    move_uploaded_file(

        $file['tmp_name'],

        $folder.$filename

    );



    return $filename;


}



/*
|--------------------------------------------------------------------------
| Order Status Badge
|--------------------------------------------------------------------------
*/


function statusClass($status)

{

    return strtolower(

        str_replace(

            ' ',

            '-',

            $status

        )

    );


}


?>