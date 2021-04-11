<?
    // include php files to use functions in other php files
    include "simple_html_dom.php";

    // receive ajax menu data
    $menu = $_POST['menu'];

    // parse received ajax params data and save to postArr
    parse_str($_POST['params'], $postArr);

    // header array contains ID and Key value
    $header = array();
    $header['key'] = '';        // key 값 입력 필요

    $total = array();
    $res_seller = array();

    // if menu is category number search
    //if($menu == "NO_search"){
        $cate_NO = $postArr['cate_NO'];
        $sort_method = $postArr['sort_method'];
        $num_prd = $postArr['num_prd'];


        $start = microtime(true);

        $res_category = category($cate_NO, $sort_method, $num_prd, $header);
        $total['category_time']  = microtime(true) - $start;

        $start = microtime(true);
        //$res_seller['data'] = seller($res_category);
        $res_seller['data'] = seller2($res_category);
        $total['seller_time'] = microtime(true) - $start;

    //}
    $res_seller['time'] = $total;
    echo JSON_encode($res_seller);

// get product information by category number
function category($cate_NO, $sort_method, $num_prd, $header)
{
    // 11st category api path
    $path = 'http://openapi.11st.co.kr/openapi/OpenApiService.tmall';

    // save inputs and options
    $req = array();
    $req['key'] = $header["key"];
    $req['apiCode'] = "CategoryInfo";
    $req['option'] = "Products";
    $req['sortCd'] = $sort_method;

    // option
    $opt = array();
    $opt['is_get']  =   TRUE;
    $opt['is_json'] =   FALSE;
    $opt['is_multipart'] =  FALSE;


    $seller_check = array();        // save seller id if no duplicate value

    $arr = array();                 // save this array to $res_value
    $res = array();                 // save response of sendCURL
    $res_value = array();           // save data to return


    $categoryCode = explode(',', $cate_NO);


    $max_page_size = 250;           // maximum number of products for each page
    $total_page = (int)($num_prd/$max_page_size + 1);       // typecasting

    if(($num_prd % $max_page_size) == 0)
    {
        $total_page--;
    }

    // loop n times: n = number of inputs
    foreach($categoryCode as $cate_NO)
    {
        $cate_NO = str_replace('\t', '', $cate_NO);
        $req['categoryCode'] = trim($cate_NO);

        // put duplicate check here
        $i = 1;
        if($total_page > 1)
        {
            for($i; $i < $total_page; $i++)
            {
                $req['pageNum'] = $i;
                $req['pageSize'] = $max_page_size;
                $res[] = sendCURL($path, $header, $req, $opt);
            }
       
            }
        }
        $rest = $num_prd % $max_page_size;
        $req['pageNum'] = $i;
        $req['pageSize'] = $rest;
        if($rest == 0 && $num_prd != 0)
        {
            $req['pageSize'] = $max_page_size;
        }

        $res[] = sendCURL($path, $header, $req, $opt);
    }

        // loop n times: n = number of inputs
        foreach($res as $info)
        {
            // XML -> obj
            $save = simplexml_load_string($info, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);


            //check overlap, if no, send req, else do nothing
            // === return true when value and type same
            $product = $save->Products->Product;

            // loop n times: n = number of products
            foreach($product as $p)
            {
                $seller = $p->Seller;
                if(false === array_search($seller, $seller_check)){
                    $arr['category_name'] = trim($save->Category->CategoryName);
                    $arr['product_code'] = $p->ProductCode;
                    $arr['seller_name'] = $p->Seller;
                    $arr['category_code'] = trim($save->Category->CategoryCode);
                    $arr['seller_nick'] = trim($p->SellerNick);
                    $res_value[] = $arr;
                    $seller_check[] = trim($seller);
                }
            }
        }
    return $res_value;
}
function seller2($cate)
{
    // url for 11st seller shop page
    $url = 'http://shop.11st.co.kr/store/';


    $tmp = array();                 // save return value
    $time = array();                // save calculated execution time


    foreach($cate as $cate_info)
    {
        $arr = array();
        // save url
        $name = $cate_info['seller_name'];

        // string -> html
        $start = microtime(true);
        $originHTML = sendCURL($url.$name, array(), array(), array());
        $time['1. sendCURL_time'] = round(microtime(true) - $start, 3);

        // encoding to Korean
        $originHTML = iconv('EUC-KR', 'UTF-8', $originHTML);
        $originHTML = preg_replace('!\s+!',' ',$originHTML);
        preg_match('/<dl\sclass=\"store_info_detail\">(.+)<\/dl>/', $originHTML, $originHTMLs);

        $originHTML = "<html>".$originHTMLs[1]."</html>";

        $start = microtime(true);
        $res = str_get_html($originHTML);


        $time['2. str_get_time'] = round(microtime(true) - $start, 3);


        $j = 0;

        $arr['CategoryName'] = $cate_info['category_name'];
        $arr['CategoryCode'] = $cate_info['category_code'];

        // find class=store_info_detail and dd inside class
        try{
            $f = $res->find('dd');
        }catch(Exception  $e)
        {
            echo $res."\n";
            print_r($e);
        }
                              

        // find class=store_info_it and save it to $name
        $arr['SellerName'] = $cate_info['seller_nick'];

        $arr['Seller'] = '';
        $arr['ShopName'] = '';
        $arr['SellerPhone'] = '';
        $arr['SellerMail'] = '';
        $arr['SellerAddr'] = '';
        $arr['Classify'] = '';
        $arr['License'] = '';
        $arr['Report'] = '';
        $arr['PostAddr'] = '';
        $start = microtime(true);
        foreach($f as $element)
        {
            if($j == 0)
            {
                $arr['Seller'] = $element->plaintext;
            }
            if($j == 1)
            {
                $arr['ShopName'] = $element->plaintext;
            }
            if($j == 2)
            {
                $arr['Classify'] = $element->plaintext;
            }
            if($j == 3)
            {
                $arr['License'] = $element->plaintext;
            }
            if($j == 4)
            {
                $arr['Report'] = $element->plaintext;
            }
            if($j == 5)
            {
                $arr['SellerPhone'] = $element->plaintext;
            }
            else if($j == 6)
            {
                $arr['SellerMail'] = $element->plaintext;
            }
            else if($j == 7)
            {
                $arr['SellerAddr'] = $element->plaintext;
                $arr['PostAddr'] = postAddr($arr['SellerAddr']);
            }
            $j++;
        }
        $time['3. input_time'] = round(microtime(true) - $start, 4);
        $arr['time'] = $time;
        $tmp[] = $arr;         
        // initialize
        $res->clear();
        $originalHTML = null;
    }

    return $tmp;
}

function postAddr($addr)
{
    /* URL if requests exceeds use another url */

    $req = array();

    // option
    $opt = array();
    $opt['is_get'] = TRUE;
    $opt['is_json'] = FALSE;
    $opt['is_multipart'] = FALSE;

    $addr = explode('(', $addr);

    $addr = explode(',', $addr[0]);

   $result = preg_match("/[가리동로(번길)길](\s?)(\d+)(-(\d+))?/u", $addr[0], $test);

    if($result == 1)
    {
        $addr = explode($test[0], $addr[0]);

        $addr[0] = $addr[0].$test[0];
    }

    /* first URL
    $path = 'http://openapi.epost.go.kr/postal/retrieveNewZipCdService/retrieveNewZipCdService/getNewZipCdList';
    $key = 'LgjG4hXkNPu6VWebwRK7tH5NZwedS363f1AxUg3VKX1WGB9UcQuRHA%2B4%2F%2BBhHzdtXOF32Y%2FATJlVmREf3eAyEQ%3D%3D';
    $req['ServiceKey'] = urldecode($key);
    $req['srchwrd'] = $addr[0];
    first URL end */

    /* second URL */
    $path = 'http://biz.epost.go.kr/KpostPortal/openapi2';
    $req['regkey'] = '151659f10963c7fc61537165928412';
    $req['target']='postNew';
    $req['query'] = $addr[0];
    /* second URL end */

    $post = sendCURL($path, array(), $req, $opt);

    $save = simplexml_load_string($post, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);

    /* save first URL POST*/
    //$post = trim($save[0]->newZipCdList->zipNo);

    /* save second URl POST */
    $post = trim($save->itemlist->item->postcd);

    return $post;
}
     function sendCURL($path, $header, $req, $opt)
{
    $c = curl_init();

    if($opt['is_get'])// 1. GET 전송
    {
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
        //echo $buffer;
        $data = $buffer;
    }
    curl_close ($c);
    return $data;
}

    function httpGet($url,$params=array())
    {
        $postData = '';
        //create name value pairs seperated by &
        foreach($params as $k => $v)
        {
            $postData .= $k . '='.$v.'&';
        }
        $postData = rtrim($postData, '&');

        $url .= "?".$postData;
        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

        //--------------
        // OPTION
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
        curl_setopt($ch,CURLOPT_MAXREDIRS,2);//only 2 redirects

        //--------------
        // Fake User Agent
        curl_setopt($ch,CURLOPT_USERAGENT,getRandomUserAgent());

        //curl_setopt($ch,CURLOPT_HEADER, false);
        //$output=utf8_decode(curl_exec($ch));
        $output=(curl_exec($ch));


        $error = "";
        if($output === false)
        {
            $error .= "Error Number:".curl_errno($ch)."<br>";
            $error .= "Error String:".curl_error($ch);
        }
        curl_close($ch);

        return $output.$error;
    }
    function getRandomUserAgent()
    {
        $userAgents=array(
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"
            ,"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)"
            ,"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)"
            ,"Opera/9.20 (Windows NT 6.0; U; en)"
            ,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; en) Opera 8.50"
            ,"Mozilla/4.0 (compatible; MSIE 6.0; MSIE 5.5; Windows NT 5.1) Opera 7.02 [en]"
            ,"Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; fr; rv:1.7) Gecko/20040624 Firefox/0.9"
            ,"Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/48 (like Gecko) Safari/48"
            ,"Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.33 Safari/537.36"
        );
        $random = rand(0,count($userAgents)-1);

        return $userAgents[$random];
    }

?>