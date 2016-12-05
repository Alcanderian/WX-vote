<?php
header("Content-type:text/html;charset=utf-8");

define('VOTE_LIMIT', 3);

function checkSignature() {
    $signature = $_GET["signature"];
    $timestamp = $_GET["timestamp"];
    $nonce = $_GET["nonce"];
    $token = "whatthefackand";
    $tmpArr = array($token, $timestamp, $nonce);
    sort($tmpArr, SORT_STRING);
    $tmpStr = implode($tmpArr);
    $tmpStr = sha1($tmpStr);

    if ($tmpStr == $signature) {
        return true;
    } else {
        return false;
    }
}

function toUtf($s) {
    return mb_convert_encoding($s, "utf-8");
}

///////////////////////////////////////////////////////////

function createResponse() {

/*/////////////////////////////////////////////////////////
接入公众号时使用
    if(checkSignature()) {
        return $_GET["echostr"];
    }
/////////////////////////////////////////////////////////*/

    $xmlStr = $GLOBALS["HTTP_RAW_POST_DATA"];
    $xml = simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA);

    $response = simplexml_load_string("<xml></xml>");
    $response->addChild("ToUserName", $xml->FromUserName);
    $response->addChild("FromUserName", $xml->ToUserName);
    $response->addChild("CreateTime", time());
    $response->addChild("MsgType", "text");

    if ($xml->MsgType == "event" && $xml->Event == "subscribe") {

        $response->addChild("Content", toUtf("欢迎关注 Maxcell 资源在线协会！"));
        return $response->asXML();

    } else if ($xml->MsgType == "text") {

        //bulid database.php yourself
        require_once "database.php";
        $db_mysqli = new mysqli($db_hostname, $db_user, $db_password, $db_database);
        if (!isset($db_mysqli)) {
            $response->addChild("Content", toUtf("服务器出错"));
            return $response->asXML();
        }

        $query = preg_replace("/\s(?=\s)/", "\\1", $xml->Content);
        $query = trim($query);
        $query = explode(" ", $query, 2);

        if ($query && $query[0] == "投票") {
            //show list
            if ((count($query) == 1 || !isset($query[1])) && $query[0] == "投票") {
                $result = $db_mysqli->query("select * from Vote");
                $content = toUtf("参赛队伍及作品：") . "\n";
                while ($row = $result->fetch_object()) {
                    $content .= $row->tid . "." . toUtf($row->name) . "<" . toUtf($row->work) . ">\n";
                }
                $response->addChild("Content", $content);
                return $response->asXML();
            }

            if ($query[0] == "投票") {

                $result = $db_mysqli->query("select * from User where uid = '" . (string)$xml->FromUserName . "'");
                $voted_times = $result->num_rows;
                if (isset($voted_times) && $voted_times >= VOTE_LIMIT) {
                    $response->addChild("Content", toUtf("您已经投") . $voted_times . toUtf("次票了，一人只能投")
                        . VOTE_LIMIT . toUtf("次，谢谢参与!"));
                    return $response->asXML();
                }

                $id = explode(" ", $query[1]);

                if ((isset($voted_times) ? $voted_times : 0) + count($id) > VOTE_LIMIT) {
                    $response->addChild("Content", toUtf("您已经投") . (isset($voted_times) ? $voted_times : 0)
                        . toUtf("次票了，一人只能投") . VOTE_LIMIT . toUtf("次，谢谢参与!"));
                    return $response->asXML();
                }

                $count_id = count($id);
                if (count(array_unique($id)) != $count_id) {
                    $response->addChild("Content", toUtf("不能重复投一队，谢谢参与!"));
                    return $response->asXML();
                }

                if (isset($voted_times) && $voted_times != 0) {
                    while ($row = $result->fetch_object()) {
                        if (in_array($row->tid, $id)) {
                            $response->addChild("Content", toUtf("不能重复投一队，谢谢参与!"));
                            return $response->asXML();
                        }
                    }
                }

                if ($count_id != 0) {
                    foreach ($id as $i) {
                        if (!($i >= 1 && $i <= 10)) {
                            $content = toUtf("队伍编号不合法\n\n");
                            $result = $db_mysqli->query("select * from Vote");
                            $content .= toUtf("参赛队伍及作品：\n");
                            while ($row = $result->fetch_object()) {
                                $content .= $row->tid . "." . toUtf($row->name) . "<" . toUtf($row->work) . ">\n";
                            }
                            $response->addChild("Content", $content);
                            return $response->asXML();
                        }
                    }

                    foreach ($id as $i) {
                        $db_mysqli->query("update Vote SET voted = voted + 1 WHERE tid = '" . $i . "'");
                        $db_mysqli->query("insert into User (uid, tid) values('" . (string)$xml->FromUserName
                            . "', '$i')");
                    }

                    $content = toUtf("投票成功！\n\n");
                    $content .= toUtf("你投了 ");
                    foreach ($id as $i) {
                        $content .= $i . " ";
                    }
                    $content .= toUtf("号队伍，谢谢您的参与！");
                    $response->addChild("Content", $content);
                    return $response->asXML();

                } else {
                    $result = $db_mysqli->query("select * from Vote");
                    $content = toUtf("参赛队伍及作品：") . "\n";
                    while ($row = $result->fetch_object()) {
                        $content .= $row->tid . ". " . toUtf($row->name) . " - < " . toUtf($row->work) . " >\n";
                    }
                    $response->addChild("Content", $content);
                    return $response->asXML();
                }
            }
            $db_mysqli->close();
        } else if ($xml->Content == "节目单") {

            $response->MsgType = "news";
            $response->addChild("ArticleCount", "1");
            $response->addChild("Articles", "");
            $response->Articles->addChild("item", "");
            $response->Articles->item->addChild("Title", toUtf("橙名夜节目单"), "UTF-8");
            $content = toUtf("1227 橙名夜节目单\n");
            $response->Articles->item->addChild("Description", $content);
            $response->Articles->item->addChild("PicUrl",
                "http://maxcellweixin.sinaapp.com/webShow/vote/images/head.jpg");
            $response->Articles->item->addChild("Url", "http://www.dwz.cn/showList");
            return $response->asXML();

        } else if ($xml->Content == "弹幕") {

            $response->addChild("Content", toUtf("点击下面的链接发弹幕！")
                . "\n\nhttp://danmu.maxcell.com.cn/");
            return $response->asXML();

        } else {

            $content = toUtf("输入有误，请重试！\n\n输入投票和队伍编号可参与投票，如“投票 1 2 3”。\n\n"
                . "输入“弹幕”可获取弹幕发送链接。\n\n输入“节目单”可查看橙名夜节目单。\n\n");
            $result = $db_mysqli->query("select * from Vote");
            $content .= toUtf("参赛队伍及作品：") . "\n";
            while ($row = $result->fetch_object()) {
                $content .= $row->tid . "." . toUtf($row->name) . "<" . toUtf($row->work) . ">\n";
            }
            $response->addChild("Content", $content);
            return $response->asXML();

        }
    } else {

        return "";

    }
}

echo createResponse();

?>
