<?php


function clean($data){

    return htmlspecialchars(
        trim($data),
        ENT_QUOTES,
        'UTF-8'
    );

}




function redirect($page){

    header("Location: ".$page);

    exit();

}




function uploadImage($file,$folder){



    if($file['error'] != 0){

        return null;

    }



    $ext=strtolower(
        pathinfo(
            $file['name'],
            PATHINFO_EXTENSION
        )
    );



    $allowed=[
        "jpg",
        "jpeg",
        "png",
        "webp"
    ];



    if(!in_array($ext,$allowed)){

        return null;

    }




    $name=time()."_".$file['name'];



    move_uploaded_file(

        $file['tmp_name'],

        "../assets/uploads/".$folder."/".$name

    );



    return $name;


}




function formatPrice($price){

    return "RM ".number_format($price,2);

}



?>