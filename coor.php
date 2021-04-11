
<?
    // receive ajax menu data
    $menu = $_POST['menu'];

    // parse received ajax params data and save to postArr
    parse_str($_POST['params'], $postArr);

    // header array contains ID and Key value
    $header = array();
    $header[] = "";     // key 값 입력
    $header[] = "";     // key 값 입력

    // goto each api function according to selected menu
    if($menu == "geocode"){
        // addr: input address

        //res: response from geocode function
        $res = geocode($header);
    }

    echo json_encode($res);

// geocoding api function
function geocode($header)
{
    // naver geocode api path
    $path = "https://naveropenapi.apigw.ntruss.com/map-geocode/v2/geocode";

    // option
    $opt = array();
    $opt["is_get"]  =   TRUE;
    $opt["is_json"] =   FALSE;
    $opt["is_multipart"] =  FALSE;

    // 여기에 addr_id 랑 address 배열 추기


    $count = 0;
    $save = array();
    $tmp = array();


    foreach($address as $addr)
    {
        // save input address to $req, and request $req to naver api
        // must use $req["query"]
        $req = array();

        $addr = explode(',', $addr);
        $addr= explode('(', $addr[0]);

        $req["query"] = $addr[0];

        $save[]= sendCURL($path, $header, $req, $opt);

    }

    return $save;

}


function sendCURL($path, $header, $req, $opt)
{
    $c = curl_init();

    if($opt['is_get'])// 1. GET 전송
    {
        if(count($req))
            $path .= "?" . http_build_query($req);
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
    }
    curl_close ($c);
    return $data;
}
?>