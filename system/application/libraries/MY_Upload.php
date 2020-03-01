<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Upload extends CI_Upload {

    public $min_width  = 0;
    public $min_height = 0;
    public $tmp_name   = '';

    public function __construct($props = array())
    {
        parent::__construct();
    }

    /**
     * Initialize preferences
     *
     * @param	array
     * @return	void
     */
    public function initialize($config = array())
    {
        $defaults = array(
            'max_size'			=> 0,
            'max_width'			=> 0,
            'max_height'		=> 0,
            'min_width'			=> 0,
            'min_height'		=> 0,
            'max_filename'		=> 0,
            'allowed_types'		=> "",
            'file_temp'			=> "",
            'file_name'			=> "",
            'orig_name'			=> "",
            'file_type'			=> "",
            'file_size'			=> "",
            'file_ext'			=> "",
            'upload_path'		=> "",
            'overwrite'			=> FALSE,
            'encrypt_name'		=> FALSE,
            'is_image'			=> FALSE,
            'image_width'		=> '',
            'image_height'		=> '',
            'image_type'		=> '',
            'image_size_str'	=> '',
            'error_msg'			=> array(),
            'mimes'				=> array(),
            'remove_spaces'		=> TRUE,
            'xss_clean'			=> FALSE,
            'temp_prefix'		=> "temp_file_",
            'client_name'		=> ''
        );


        foreach ($defaults as $key => $val)
        {
            if (isset($config[$key]))
            {
                $method = 'set_'.$key;
                if (method_exists($this, $method))
                {
                    $this->$method($config[$key]);
                }
                else
                {
                    $this->$key = $config[$key];
                }
            }
            else
            {
                $this->$key = $val;
            }
        }

        // if a file_name was provided in the config, use it instead of the user input
        // supplied file name for all uploads until initialized again
        $this->_file_name_override = $this->file_name;
    }

    /**
     * Perform the file upload
     *
     * @return	bool
     */
    public function do_upload($field = 'userfile')
    {
        // Is $_FILES[$field] set? If not, no reason to continue.
        if ( ! isset($_FILES[$field]))
        {
            $this->set_error('upload_no_file_selected');
            return FALSE;
        }

        // Is the upload path valid?
        if ( ! $this->validate_upload_path())
        {
            // errors will already be set by validate_upload_path() so just return FALSE
            return FALSE;
        }

        // Was the file able to be uploaded? If not, determine the reason why.
        if ( ! is_uploaded_file($_FILES[$field]['tmp_name']))
        {
            $error = ( ! isset($_FILES[$field]['error'])) ? 4 : $_FILES[$field]['error'];

            switch($error)
            {
                case 1:	// UPLOAD_ERR_INI_SIZE
                    $this->set_error('upload_file_exceeds_limit');
                    break;
                case 2: // UPLOAD_ERR_FORM_SIZE
                    $this->set_error('upload_file_exceeds_form_limit');
                    break;
                case 3: // UPLOAD_ERR_PARTIAL
                    $this->set_error('upload_file_partial');
                    break;
                case 4: // UPLOAD_ERR_NO_FILE
                    $this->set_error('upload_no_file_selected');
                    break;
                case 6: // UPLOAD_ERR_NO_TMP_DIR
                    $this->set_error('upload_no_temp_directory');
                    break;
                case 7: // UPLOAD_ERR_CANT_WRITE
                    $this->set_error('upload_unable_to_write_file');
                    break;
                case 8: // UPLOAD_ERR_EXTENSION
                    $this->set_error('upload_stopped_by_extension');
                    break;
                default :   $this->set_error('upload_no_file_selected');
                    break;
            }

            return FALSE;
        }


        // Set the uploaded data as class variables
        $this->file_temp = $_FILES[$field]['tmp_name'];
        $this->file_size = $_FILES[$field]['size'];
        $this->file_type = preg_replace("/^(.+?);.*$/", "\\1", $_FILES[$field]['type']);
        $this->file_type = strtolower(trim(stripslashes($this->file_type), '"'));
        $this->file_name = $this->_prep_filename($_FILES[$field]['name']);
        $this->file_ext	 = $this->get_extension($this->file_name);
        $this->client_name = $this->file_name;

        // Is the file type allowed to be uploaded?
        if ( ! $this->is_allowed_filetype())
        {
            $this->set_error('upload_invalid_filetype');
            return FALSE;
        }

        // if we're overriding, let's now make sure the new name and type is allowed
        if ($this->_file_name_override != '')
        {
            $this->file_name = $this->_prep_filename($this->_file_name_override);

            // If no extension was provided in the file_name config item, use the uploaded one
            if(strpos($this->_file_name_override, '.') === FALSE)
            {
                $this->file_name .= $this->file_ext;
            }

            // An extension was provided, lets have it!
            else
            {
                $this->file_ext	 = $this->get_extension($this->_file_name_override);
            }

            if ( ! $this->is_allowed_filetype(TRUE))
            {
                $this->set_error('upload_invalid_filetype');
                return FALSE;
            }
        }

        // Convert the file size to kilobytes
        if ($this->file_size > 0)
        {
            $this->file_size = round($this->file_size/1024, 2);
        }

        // Is the file size within the allowed maximum?
        if ( ! $this->is_allowed_filesize())
        {
            $this->set_error('upload_invalid_filesize', [$this->max_size/1024/1024 . "MB"]);
            return FALSE;
        }

        // Are the image dimensions within the allowed size?
        // Note: This can fail if the server has an open_basdir restriction.
        if ( ! $this->is_allowed_dimensions())
        {
            $this->set_error('upload_invalid_dimensions', [$this->max_width, $this->max_height]);
            return FALSE;
        }

        // Are the image dimensions within the allowed size?
        // Note: This can fail if the server has an open_basdir restriction.
        if ( ! $this->is_allowed_dimensions_min())
        {
            $this->set_error('upload_invalid_dimensions_min', [$this->min_width, $this->min_height]);
            return FALSE;
        }

        // Sanitize the file name for security
        $this->file_name = $this->clean_file_name($this->file_name);

        // Truncate the file name if it's too long
        if ($this->max_filename > 0)
        {
            $this->file_name = $this->limit_filename_length($this->file_name, $this->max_filename);
        }

        // Remove white spaces in the name
        if ($this->remove_spaces == TRUE)
        {
            $this->file_name = preg_replace("/\s+/", "_", $this->file_name);
        }

        /*
         * Validate the file name
         * This function appends an number onto the end of
         * the file if one with the same name already exists.
         * If it returns false there was a problem.
         */
        $this->orig_name = $this->file_name;

        if ($this->overwrite == FALSE)
        {
            $this->file_name = $this->set_filename($this->upload_path, $this->file_name);

            if ($this->file_name === FALSE)
            {
                return FALSE;
            }
        }

        /*
         * Run the file through the XSS hacking filter
         * This helps prevent malicious code from being
         * embedded within a file.  Scripts can easily
         * be disguised as images or other file types.
         */
        if ($this->xss_clean)
        {
            if ($this->do_xss_clean() === FALSE)
            {
                $this->set_error('upload_unable_to_write_file');
                return FALSE;
            }
        }

        /*
         * Move the file to the final destination
         * To deal with different server configurations
         * we'll attempt to use copy() first.  If that fails
         * we'll use move_uploaded_file().  One of the two should
         * reliably work in most environments
         */
        if ( ! @copy($this->file_temp, $this->upload_path.$this->file_name))
        {
            if ( ! @move_uploaded_file($this->file_temp, $this->upload_path.$this->file_name))
            {
                $this->set_error('upload_destination_error');
                return FALSE;
            }
        }

        /*
         * Set the finalized image dimensions
         * This sets the image width/height (assuming the
         * file was an image).  We use this information
         * in the "data" function.
         */
        $this->set_image_properties($this->upload_path.$this->file_name);

        return TRUE;
    }

    /**
     * Set an error message
     *
     * @param	string
     * @return	void
     */
    public function set_error($msg, $params = array())
    {
        $CI =& get_instance();
        $CI->lang->load('upload');

        if (is_array($msg))
        {
            foreach ($msg as $val)
            {
                $msg = ($CI->lang->line($val) == FALSE) ? $val : $CI->lang->line($val);
                if(!empty($params)){
                    $msg = vsprintf($msg, $params);
                }
                $this->error_msg[] = $msg;
                log_message('error', $msg);
            }
        }
        else
        {
            $msg = ($CI->lang->line($msg) == FALSE) ? $msg : $CI->lang->line($msg);
            if(!empty($params)){
                $msg = vsprintf($msg, $params);
            }
            $this->error_msg[] = $msg;
            log_message('error', $msg);
        }
    }

    /**
     * Set Maximum Image Width
     *
     * @param	integer
     * @return	void
     */
    public function set_min_width($n)
    {
        $this->min_width = ((int) $n < 0) ? 0: (int) $n;
    }

    // --------------------------------------------------------------------

    /**
     * Set Maximum Image Height
     *
     * @param	integer
     * @return	void
     */
    public function set_min_height($n)
    {
        $this->min_height = ((int) $n < 0) ? 0: (int) $n;
    }

    /**
     * Verify that the image is within the allowed width/height
     *
     * @return	bool
     */
    public function is_allowed_dimensions_min()
    {
        if ( ! $this->is_image())
        {
            return TRUE;
        }

        if (function_exists('getimagesize'))
        {
            $D = @getimagesize($this->file_temp);

            if ($this->min_width > 0 AND $D['0'] < $this->min_width)
            {
                return FALSE;
            }

            if ($this->min_height > 0 AND $D['1'] < $this->min_height)
            {
                return FALSE;
            }

            return TRUE;
        }

        return TRUE;
    }


    /**
     * @param $path
     * check exist and create if not exist dir
     */
    public function exist_create_dir($path)
    {
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
            $CI =& get_instance();
            $CI->load->helper('file');
            @write_file($path . '/index.html', '<p>Directory access is forbidden.</p>');
        }
    }
}











?>