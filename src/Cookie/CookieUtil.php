<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  HttpManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/httpmanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Http\Cookie;

final class CookieUtil
{
    protected const DATE_FORMAT = 'D, d-M-Y H:i:s T';

    /**
     * Handles dates as defined by RFC 2616 section 3.3.1, and also some other
     * non-standard, but common formats.
     *
     * @var array
     */
    private static $dateFormats = [
        'D, d M y H:i:s T',
        'D, d M Y H:i:s T',
        'D, d-M-y H:i:s T',
        'D, d-M-Y H:i:s T',
        'D, d-m-y H:i:s T',
        'D, d-m-Y H:i:s T',
        'D M j G:i:s Y',
        'D M d H:i:s Y T',
    ];

    /**
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/BrowserKit/Cookie.php
     *
     * @param string $dateValue
     *
     * @return \DateTime
     *
     * @throws UnexpectedValueException if we cannot parse the cookie date string.
     */
    public static function parseDate($dateValue)
    {
        foreach (self::$dateFormats as $dateFormat) {
            if (false !== $date = \DateTime::createFromFormat($dateFormat, $dateValue, new \DateTimeZone('GMT'))) {
                return $date;
            }
        }

        // attempt a fallback for unusual formatting
        if (false !== $date = date_create($dateValue, new \DateTimeZone('GMT'))) {
            return $date;
        }

        throw new \UnexpectedValueException(sprintf('Unparseable cookie date string "%s"', $dateValue));
    }

    /**
     * Convert cookie instance to string.
     *
     * @see http://www.w3.org/Protocols/rfc2109/rfc2109
     *
     * @return string
     */
    public static function toString(CookieFactory $cookie): string
    {
        $header = [
            rawurlencode($cookie->getName()) . '=' . rawurlencode($cookie->getValue() ?? 'deleted'),
        ];

        if ($cookie->getExpires() > 0) {
            $header[] = 'Expires='.gmdate(self::DATE_FORMAT, $cookie->getExpires());
            $header[] = 'Max-Age='.$cookie->getMaxAge();
        } elseif (
            null === $cookie->getValue() &&
            (0 === $cookie->getExpires() ||null == $cookie->getExpires())
        ) {
            $header[] = 'Expires='.gmdate(self::DATE_FORMAT, time() - 31536001);
            $header[] = 'Max-Age=0';
        }

        if (!empty($cookie->getPath())) {
            $header[] = 'Path='.$cookie->getPath();
        }

        if (!empty($cookie->getDomain())) {
            $header[] = 'Domain='.$cookie->getDomain();
        }

        if ($cookie->isSecure()) {
            $header[] = 'Secure';
        }

        if ($cookie->isHttpOnly()) {
            $header[] = 'HttpOnly';
        }

        if (null !== $cookie->getSameSite()) {
            $header[] = 'SameSite='.$cookie->getSameSite();
        }

        return join('; ', $header);
    }

    /**
     * Validates the name attribute.
     *
     * @see http://tools.ietf.org/search/rfc2616#section-2.2
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException If the name is empty or contains invalid characters.
     */
    public static function validateName($name)
    {
        if (strlen($name) < 1) {
            throw new \InvalidArgumentException('The name cannot be empty');
        }

        if (preg_match("/[=,; \t\r\n\013\014]/", $name)) {
            throw new \InvalidArgumentException("Cookie name cannot contain these characters: =,; \\t\\r\\n\\013\\014 ({$name})");
        }

        // Name attribute is a token as per spec in RFC 2616
        if (preg_match('/[\x00-\x20\x22\x28-\x29\x2C\x2F\x3A-\x40\x5B-\x5D\x7B\x7D\x7F]/', $name)) {
            throw new \InvalidArgumentException(sprintf('The cookie name "%s" contains invalid characters.', $name));
        }
    }

    /**
     * Validates a value.
     *
     * Per RFC 7230, only VISIBLE ASCII characters, spaces, and horizontal
     * tabs are allowed in values; header continuations MUST consist of
     * a single CRLF sequence followed by a space or horizontal tab.
     *
     * @see http://tools.ietf.org/html/rfc6265#section-4.1.1
     *
     * @param string|null $value
     *
     * @throws \InvalidArgumentException If the value contains invalid characters.
     */
    public static function validateValue($value)
    {
        if (isset($value)) {
            // Look for:
            // \n not preceded by \r, OR
            // \r not followed by \n, OR
            // \r\n not followed by space or horizontal tab; these are all CRLF attacks
            if (preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $value)) {
                throw new \InvalidArgumentException(sprintf('The cookie value "%s" contains invalid characters.', $value));
            }

            if (preg_match('/[^\x21\x23-\x2B\x2D-\x3A\x3C-\x5B\x5D-\x7E]/', $value)) {
                throw new \InvalidArgumentException(sprintf('The cookie value "%s" contains invalid characters.', $value));
            }
        }
    }

    /**
     * Validates a Max-Age attribute.
     *
     * @param int|null $maxAge
     *
     * @throws \InvalidArgumentException If the Max-Age is not an empty or integer value.
     */
    public static function validateMaxAge($maxAge)
    {
        if (isset($maxAge)) {
            if (!is_int($maxAge)) {
                throw new \InvalidArgumentException('Max-Age must be integer');
            }
        }
    }

    /**
     * Remove the leading '.' and lowercase the domain as per spec in RFC 6265.
     *
     * @see http://tools.ietf.org/html/rfc6265#section-4.1.2.3
     * @see http://tools.ietf.org/html/rfc6265#section-5.1.3
     * @see http://tools.ietf.org/html/rfc6265#section-5.2.3
     *
     * @param string|null $domain
     *
     * @return string
     */
    public static function normalizeDomain($domain)
    {
        if (isset($domain)) {
            $domain = ltrim(strtolower($domain), '.');
        }

        return $domain;
    }

    /**
     * Processes path as per spec in RFC 6265.
     *
     * @see http://tools.ietf.org/html/rfc6265#section-5.1.4
     * @see http://tools.ietf.org/html/rfc6265#section-5.2.4
     *
     * @param string|null $path
     *
     * @return string
     */
    public static function normalizePath($path)
    {
        $path = rtrim($path, '/');

        if (empty($path) || '/' !== substr($path, 0, 1)) {
            $path = '/';
        }

        return $path;
    }
}