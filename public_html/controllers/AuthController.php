<?php

require_once 'MoodleController.php'; 

class AuthController
{
    public function login(string $matricula, string $senha): bool
    {
        $moodleServer = 'moodle.canoas.ifrs.edu.br'; 
        $moodleAuth = MoodleController::generateToken($matricula, $senha, $moodleServer);

        if (isset($moodleAuth->token)) {
           
            $_SESSION['user_token'] = $moodleAuth->token;
            $_SESSION['user_matricula'] = $matricula;

            
            return true;
        }

        return false;
    }
}
