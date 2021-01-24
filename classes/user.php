<?php

class user
{
    protected static PDO $conn;

    /**
     * user constructor.
     * @param PDO $conn
     */
    public function __construct(PDO $conn)
    {
        self::$conn = $conn;
    }

    /**
     * @param int $userID
     * @return array
     */
    public static function getUserByID(int $userID): array
    {
        $user = self::$conn->prepare("SELECT * FROM users WHERE id=:id");
        $user->execute(['id' => $userID]);
        $user = $user->fetch();
        return is_array($user) ? $user : [];
    }

    /**
     * @param string $username
     * @return array
     */
    public static function getUserByUsername(string $username): array
    {
        $user = self::$conn->prepare("SELECT * FROM users WHERE username=:username");
        $user->execute(['username' => $username]);
        $user = $user->fetch();
        return is_array($user) ? $user : [];
    }

    /**
     * @param string $email
     * @return array
     */
    public static function getUserByEmail(string $email): array
    {
        $user = self::$conn->prepare("SELECT * FROM users WHERE email=:email");
        $user->execute(['email' => $email]);
        $user = $user->fetch();
        return is_array($user) ? $user : [];
    }

    /**
     * @return array
     */
    public static function getUsers(): array
    {
        $users = self::$conn->query("SELECT * FROM users");
        return $users->fetchAll();
    }

    /**
     * @param string $name
     * @param string $last_name
     * @param string $email
     * @param string|null $birthday
     * @param int|null $gender
     * @param string $username
     * @param string $password
     * @return string[]
     */
    public static function registration(
        string $name,
        string $last_name,
        string $email,
        ?string $birthday,
        ?int $gender, // 1 male, 0 female
        string $username,
        string $password
    ): array
    {
        if (!empty(self::getUserByUsername($username))) {
            return ['error' => 'Username already used'];
        }
        if (!empty(self::getUserByEmail($email))) {
            return ['error' => 'Email already in use'];
        }
        $enc_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        $registration = self::$conn->prepare("INSERT INTO users (name, last_name, email, birthday, gender, username, password, registration_time, registration_ip) VALUES (:name, :last_name, :email, :birthday, :gender, :username, :password, now(), :registration_ip)");
        if ($registration->execute(['name' => $name, 'last_name' => $last_name, 'email' => $email, 'birthday' => $birthday, 'gender' => $gender, 'username' => $username, 'password' => $enc_password, 'registration_ip' => IP::get_client_ip()])) {
            return ['success' => 'registration success'];
        }
        return ['error' => 'Something wen\'t wrong'];
    }

    /**
     * @param string $login
     * @param string $password
     * @return array
     */
    public static function userLogin(string $login, string $password): array
    {
        $user = self::getUserByEmail($login);
        if (empty($user)) {
            $user = self::getUserByUsername($login);
            if (empty($user)) {
                return ['error' => 'User don\'t exist'];
            }
        }
        if (!password_verify($password, $user['password'])) {
            return ['error' => 'incorrect password!'];
        }
        $updateLoginTime = self::$conn->prepare("UPDATE users SET last_login=now(), last_login_ip=:ip WHERE id=:id");
        $updateLoginTime->execute([':ip'=>IP::get_client_ip(), 'id'=>$user['id']]);
        return ['id' => $user['id']];
    }

    public static function changePassword(int $id, string $password, string $newPassword): array
    {
        $user = self::getUserByID($id);
        if (empty($user)) {
            return ['error' => 'User don\'t exist'];
        }
        if (!password_verify($password, $user['password'])) {
            return ['error' => 'incorrect password!'];
        }
        if ($password === $newPassword) {
            return ['error' => 'New password is same as old one'];
        }
        $enc_password = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        $updatePassword = self::$conn->prepare('UPDATE users SET password=:password WHERE id=:id');
        if ($updatePassword->execute(['password' => $enc_password, 'id' => $id])) {
            return ['success' => 'Password has been changed'];
        }
        return ['error' => 'Something wen\'t wrong'];
    }
}