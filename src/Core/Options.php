<?php
declare(strict_types=1);

namespace Ersaal\Core;

class Options
{
    private string $prefix = 'ersaal_';

    /**
     * Get an option value. Prioritizes wp-config.php constants if they exist.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $constant_name = 'ERSAAL_' . strtoupper($key);
        
        if (defined($constant_name)) {
            return constant($constant_name);
        }

        return get_option($this->prefix . $key, $default);
    }

    /**
     * Set an option value in the database.
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set(string $key, $value): bool
    {
        return update_option($this->prefix . $key, $value);
    }

    /**
     * Delete an option from the database.
     * 
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return delete_option($this->prefix . $key);
    }
}
