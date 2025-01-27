<?php

class MoodleController
{

    public static function generateToken(string $username, string $password, string $server): object
    {
        $url = 'https://' . $server . '/login/token.php';

        $postData = http_build_query(
            array(
                'username' => $username,
                'password' => $password,
                'service' => 'moodle_mobile_app'
            )
        );

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return json_decode($result);
    }


    public static function getUserImageURL(object $user, string $token): string
    {
        if (isset($user->profileimageurl)) {
            $url = str_replace('pluginfile.php', 'webservice/pluginfile.php', $user->profileimageurl);
            return $url . '&token=' . $token; 
        }

        return 'img/user.png';  
    }


    public static function request(string $server, string $token, string $function, string|array $params = ''): object|array
    {
        $url = 'https://' . $server . '/webservice/rest/server.php?wsfunction=' . $function . self::prepareParams($params) . '&wstoken=' . $token . '&moodlewsrestformat=json';

        return json_decode(file_get_contents($url));
    }

    private static function prepareParams(string|array $params): string
    {
        if (is_string($params)) {
            $paramsBuild = $params;
        } else {
            setlocale(LC_ALL, 'us_En');
            $paramsBuild = http_build_query($params);
        }
        return !empty($paramsBuild) ? '&' . $paramsBuild : '';
    }

    public static function getUserInfo(string $server, string $token, string $username): object
    {
        $function = 'core_user_get_users_by_field';
        $params = array(
            'field' => 'username',
            'values' => array($username)
        );

        return self::request($server, $token, $function, $params)[0];
    }
}
?>
