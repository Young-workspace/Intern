<?
    // receive ajax menu data
    $menu = $_POST['menu'];

    // parse received ajax params data and save to postArr
    parse_str($_POST['params'], $postArr);

    // header array contains ID and Key value
    $header = array();
    $header["key"] = ""; //key

    if($menu == "NO_search"){
        $cate_NO = $postArr['cate_NO'];

        $res = category($cate_NO, $header);
    }

    //print_r($res);
    // xml -> obj
    $xml =  simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);


    echo json_encode($xml);


function category($cate_NO, $header)
{
    // 11st category api path
    $path = "http://openapi.11st.co.kr/openapi/OpenApiService.tmall";

    // save inputs and options
    $req = array();
    $req["key"] = $header["key"];
    $req["apiCode"] = "CategoryInfo";
    $req["categoryCode"] = $cate_NO;
    $req["option"] = "Products";
    $req["pageSize"] = "10";


    // option
    $opt = array();
    $opt["is_get"]  =   TRUE;
    $opt["is_json"] =   FALSE;
    $opt["is_multipart"] =  FALSE;

    $res = sendCURL($path, $header, $req, $opt);

    return $res;
}

function sendCURL($path, $header, $req, $opt)
{
    $c = curl_init();

    if($opt['is_get'])// 1. GET 전송
    {
        $path .= "?" . http_build_query($req);
        //echo JSON_encode($path);
    }
    else // 2. POST 전송
    {
        if($opt['is_json'])
            $req = json_encode($req);

        else if(!$opt['is_multipart'])
            $req = http_build_query($req);
    }


    //webpage to which you try to send post
    curl_setopt($c, CURLOPT_URL,  $path);
    curl_setopt($c, CURLOPT_SSLVERSION, 1);
    //curl_setopt($c, CURLOPT_POST, true);
    //curl_setopt($c, CURLOPT_HEADER, true);
    if($header)
    {
       curl_setopt($c, CURLOPT_HTTPHEADER, $header);
    }


    // data to be sent via post
    if(!$opt['is_get']) {
        curl_setopt($c, CURLOPT_POSTFIELDS, $req);
    }

    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);

    if($opt['is_multipart'])
        curl_setopt($c, CURLOPT_INFILESIZE , $req["filesize"]);

    //curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

    // Get the response and close the channel.
    ob_start();
    $res = curl_exec ($c);
    $buffer = ob_get_contents();
    ob_end_clean();


    // read the error code if there are any errors
    if(curl_errno($c))
    {
        echo "error : " . curl_error($c);
        echo $buffer;
        echo $res;
        return -1;//curl_errno($c)." : ".curl_error($c);
    } else {
        $data = json_decode($buffer, TRUE);
        //echo $buffer;
        $data = $buffer;
    }
    curl_close ($c);

}
?>
     