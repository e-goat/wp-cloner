<?php
/**
 * WP Plugin which copies the needed WP env files in order to copy an existing theme or plugin
 * It also have the ability to re-write slugs
 * dynamically append to js and php arrays
 * @package    WP_Post_Copier
 * @subpackage /includes
 * @author     Martin Duchev
 * @property   init_copier_factory() class factory
 * @version    3.2.0
 */

use phpseclib\Net\SFTP;
use phpseclib\Net\SSH2;
use phpseclib\Crypt\RSA;

class ThemeCopier
{
    private $fetch;
    private $name;
    private $slug;
    private $copy_target;
    private $region;
    private $environment;
    private $credits;
    private $jn;
    private $file_manager;
    private $server_url;
    private $key;
    private $execute_factory_settings;
    private $logo_type;

    function __construct()
    {
        $this->fetch = $this->init_fetch();

        // Props
        $this->name = $this->fetch['portal_name'];
        $this->new_slug = strtolower($this->fetch['portal_slug']);
        $this->copied_slug = strtolower($this->fetch['portal_target']);
        $this->alias = $this->fetch['portal_alias'];
        $this->region = $this->fetch['term_meta']['class_term_meta_region'];
        $this->environment = $this->fetch['term_meta']['class_term_meta_environment'];
        $this->credits = $this->fetch['term_meta']['class_term_meta'];
        $this->jn = $this->fetch['term_meta']['class_term_meta_jn'];

        $this->server_url = array(
            'REAL' => 'example-real.com',
            'DEV' => 'example-dev.com',
        );

        $this->file_manager = new WP_Filesystem_Direct(NULL);

        // Boolean, return false if we have dupe slug
        $this->execute_factory_settings = false;

        $this->logo_type = $_POST['term_meta']['class_term_logo_type'] == '2' ? 1 : 0;

        $this->init_copier_factory();
    }

    public function init_fetch()
    {
        return $_POST;
    }

    public function abstract_paths($slug = 'democlient')
    {
        // add your own folder paths below
        return array(
            'paths_to_copy' => array(
                'class' => ABSPATH . 'wp-content/plugins/your-plugin-name-here/class-' . $slug . '.php',
                'misc-js' => ABSPATH . 'wp-content/themes/your-plugin-name-here/misc-' . $slug . '.js',
                'styles' => ABSPATH . 'wp-content/themes/your-plugin-name-here/_' . $slug . '-form.scss',
                'form' => ABSPATH . 'wp-content/plugins/your-plugin-name-here/form-' . $slug . '.php',
                'navigtion' => ABSPATH . 'wp-content/plugins/your-plugin-name-here/navigation-' . $slug . '.php',
                'product' => ABSPATH . 'wp-content/plugins/your-plugin-name-here/card-' . $slug . '.php',
                'xml-folder' => ABSPATH . 'wp-content/plugins/your-plugin-name-here/xml-' . $slug . '/',
            ),
            'misc' => array(
                'global_styles_path' => ABSPATH . 'wp-content/themes/your-plugin-name-here/assets/scss/style.scss',
                'assets_js' => ABSPATH . 'wp-content/themes/your-plugin-name-here/assets/js/',
                'admin' => ABSPATH . 'wp-content/plugins/your-plugin-name-here/admin/',
                'client_logos' => ABSPATH . 'wp-content/themes/your-plugin-name-here/assets/img/client-logos/',
                'upload_dir' => wp_upload_dir()['path'],
            ),
        );
    }

    // Collect all files from $this->abstract_paths() and copy them
    public function copy_server_files()
    {
        $directories_length = count($this->abstract_paths()['paths_to_copy']);
        foreach ($this->abstract_paths()['paths_to_copy'] as $key => $value) {
            if ($this->file_manager->is_file($this->abstract_paths($this->copied_slug)['paths_to_copy'][$key])) {
                $this->file_manager->copy($this->abstract_paths($this->copied_slug)['paths_to_copy'][$key], $this->abstract_paths($this->new_slug)['paths_to_copy'][$key], false, $chmod = 0664);
            } else {
                self::copydir($this->abstract_paths($this->copied_slug)['paths_to_copy'][$key], $this->abstract_paths($this->new_slug)['paths_to_copy'][$key]);
            }
        }
    }

    public function copy_external_server_files($server): void
    {
        $key = new RSA();
        $key->loadKey(file_get_contents('/var/www/ssh_key/id_rsa'));
        $sftp = new SFTP($server);

        if (!$sftp->login('name', $key))
            throw new Exception('Login failed');

        $ssh = new SSH2($server);
        if (!$ssh->login('name', $key))
            exit('Login Failed');

        $sftp->chdir('/path');
        $sftp->mkdir('/path/' . $this->new_slug . '/');
        $sftp->mkdir('/path/' . $this->new_slug . '/static_files/');

        // Copy Decipher static folder
        $ssh->exec('cd ../../' . PHP_EOL
            . 'cd path' . PHP_EOL
            . 'scp -rp /path' . $this->copied_slug . '/static_files/*' . ' /path/' . $this->new_slug . '/static_files/' . PHP_EOL);
    }

    // Recursive slug re-naming
    public function replace_slug(): void
    {
        foreach ($this->abstract_paths($this->new_slug)['paths_to_copy'] as $key => $path) {
            self::replace_contents($path, $this->copied_slug, $this->new_slug);
        }
    }

    // Include the created slug to global css
    public function styles_config()
    {
        $global_styles_path = $this->abstract_paths()['misc']['global_styles_path'];
        $content = PHP_EOL . '@import "' . $this->new_slug . '-form";';

        return file_put_contents($global_styles_path, $content, FILE_APPEND | LOCK_EX);
    }

    public function append_to_js_array($path, $file, $string_to_append)
    {
        /* Fill any in array type string in the selected js $file */
        $clients_array_string = exec("cd " . $path . PHP_EOL . 'grep "' . $string_to_append . '" ' . $file . PHP_EOL);
        $initial_array = !self::is_double_quoted($clients_array_string) ? explode("'", $clients_array_string) : explode("\"", $clients_array_string);
        $escaped_chars_initial_array = !self::is_double_quoted($clients_array_string) ? implode("'", $initial_array) : implode("\"", $initial_array);
        $initial_array[count($initial_array) - 1] = !self::is_double_quoted($clients_array_string) ? ", '" . $this->new_slug . "'];" : ", \"" . $this->new_slug . "\"];";
        $reworked_arr_string = !self::is_double_quoted($clients_array_string) ? implode("'", $initial_array) : implode("\"", $initial_array);

        return self::replace_contents($path . 'login.js', addcslashes($escaped_chars_initial_array, "[]\""), addcslashes($reworked_arr_string, "[]\""));
    }

    public function append_aliases_to_admin($path, $file, $start, $end = '')
    {
        $file_path = $path . $file;
        $aliases_string = self::get_contents_between_strings($file_path, $start, $end);
        $initial_array = !self::is_double_quoted($aliases_string) ? explode("'", $aliases_string) : explode("\"", $aliases_string);
        $escaped_chars_string = !self::is_double_quoted($aliases_string) ? implode("'", $initial_array) : implode("\"", $initial_array);

        $alias = '';
        if ($this->alias != '') {
            $alias = $this->alias;
        } else {
            if ($this->region == 'AMS') {
                $alias = 'example-ams@example.com';
            } elseif ($this->region == 'EMEA') {
                $alias = 'example-emea@example.com';
            } elseif ($this->region == 'APAC') {
                $alias = 'example-apac@example.com';
            }
        }

        $escaped_chars_string .= '    \'' . $this->new_slug . '\' => \'' . $alias . '\', ' . '// ' . $this->name;
        // $escaped_chars_array = explode(PHP_EOL, $escaped_chars_string);
        // foreach ( $escaped_chars_array as $key => $value) {
        //     ....
        // }
        self::replace_contents($file_path, addcslashes($aliases_string, "[]/\""), addcslashes($escaped_chars_string, "[]/\""));
    }

    public function wp_admin_config()
    {
        wp_insert_term($this->new_slug, 'client', array('slug' => $this->new_slug));
        $current_client = get_term_by('slug', $this->new_slug, 'client');
        $taxonomy = get_option('taxonomy_' . $current_client->term_id);

        wp_update_term($current_client->term_id, 'client', array('name' => $this->name));

        if (isset($_POST['term_meta'])) {
            $cat_keys = array_keys($_POST['term_meta']);
            foreach ($cat_keys as $key) {
                if (isset($_POST['term_meta'][$key]))
                    $taxonomy[$key] = $_POST['term_meta'][$key];
            }
            return update_option("taxonomy_" . $current_client->term_id, $taxonomy);
        }
    }

    public function get_portal_logo()
    {
        $img_bool = true;
        $server_path = $this->abstract_paths()['misc']['upload_dir'];
        $img_type = strtolower(pathinfo($server_path . '/' . $_FILES["term_meta"]["name"]['class_term_meta_logo'], PATHINFO_EXTENSION));
        $full_path = $this->abstract_paths()["misc"]["client_logos"] . $_POST["portal_slug"] . '.' . $img_type;
        $temp_path = $_FILES["term_meta"]["tmp_name"]['class_term_meta_logo'];
        // $fileSize = $_FILES['term_meta']['size']['portal_logo'];

        if (file_exists($full_path))$img_bool = false;
        else $img_bool = true;

        if ($img_type != "jpg" && $img_type != "png" && $img_type != "jpeg" && $img_type != "gif") $img_bool = false;
        else $img_bool = true;

        if ($img_bool) return move_uploaded_file($temp_path, $full_path);
        // TO DO: Add exception handler
        else return;
    }

    public function is_slug_dupe($slug)
    {
        $allTerms = get_terms([
            'taxonomy' => 'client',
            'hide_empty' => false,
        ]);

        foreach ($allTerms as $index => $oneTerm) {
            if ($oneTerm->slug == $slug) {
                $this->execute_factory_settings = false;
                break;
            } else {
                $this->execute_factory_settings = true;
            }
        }

        return $this->execute_factory_settings;
    }

    public function init_copier_factory()
    {
        if ($this->is_slug_dupe($this->new_slug)) {
            // IF THERE IS NO LOGO UPLOADED
            if ( boolval($this->logo_type) == true ) $this->get_portal_logo();
            else $this->append_to_js_array($this->abstract_paths()['misc']['assets_js'] ,'login.js', 'var kantarBasedClients');
            // TODO: Unix SED does not read new rows, have to figure it out
            // $this->append_aliases_to_admin($this->abstract_paths()['misc']['admin'], 'class-diy-tool-plugin-admin.php', '$aliases = array(', ');');
            $this->copy_server_files();
            $this->copy_external_server_files( $this->server_url['REAL'] );
            $this->copy_external_server_files( $this->server_url['DEV'] );
            $this->replace_slug();
            $this->styles_config();
            $this->wp_admin_config();
            wp_redirect( $_SERVER["HTTP_REFERER"], $status = 302 );
        } else {
            throw new Exception("Slug already exists", 1);
        }
    }

    /***********************************************************************************/
    /***********************************************************************************/
    /***************** *-----===== UTILITY CLASS FUNCTIONS =====-----* *****************/
    /***********************************************************************************/
    /***********************************************************************************/

    // This function will only replace strings placed on one line. Multi-line strings will simply die the sed function.
    public static function replace_contents($path, $string, $replace)
    {
        $str_to_arr = preg_split("#/#", $path);
        $file_name = array_pop($str_to_arr);
        $dir = implode('/', $str_to_arr);
        $string = str_replace("\n", "\\n", $string);
        $replace = str_replace("\n", "\\n", $replace);

        if (is_file($path)) {
            shell_exec('cd ' . $dir . PHP_EOL . "sed -i \"s/" . $string . "/" . $replace . "/g\" " . $file_name);
        }
    }

    public static function copydir($srcdir, $dstdir)
    {
        if (!file_exists($dstdir)) {
            mkdir($dstdir);
            chmod($dstdir, 0775);
            $ds = opendir($srcdir);

            while ($file = readdir($ds)) {
                if ($file != '.' && $file != '..') {
                    $path = $srcdir . '/' . $file;
                    $dstpath = $dstdir . '/' . $file;

                    if (is_dir($path))
                        self::copydir($path, $dstpath);
                    else
                        copy($path, $dstpath);
                }
            }
        }

        return true;
    }

    public static function is_double_quoted($string)
    {
        return strpos($string, "\"") !== false;
    }

    public static function get_contents_between_strings($filePath, $startString, $endString)
    {
        $fileContents = file_get_contents($filePath);
        $startPosition = strpos($fileContents, $startString);
        $endPosition = strpos($fileContents, $endString, $startPosition + strlen($startString));

        if ($startPosition !== false && $endPosition !== false) {
            $startPosition += strlen($startString);
            return substr($fileContents, $startPosition, $endPosition - $startPosition);
        }
        return '';
    }
}
