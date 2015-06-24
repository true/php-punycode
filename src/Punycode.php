<?php
namespace TrueBV;

/**
 * Punycode implementation as described in RFC 3492
 *
 * @link http://tools.ietf.org/html/rfc3492
 */
class Punycode
{
    /**
     * Bootstring parameter values for host punycode
     */
    const BASE         = 36;
    const TMIN         = 1;
    const TMAX         = 26;
    const SKEW         = 38;
    const DAMP         = 700;
    const INITIAL_BIAS = 72;
    const INITIAL_N    = 128;
    const PREFIX       = 'xn--';
    const DELIMITER    = '-';

    /**
     * Encode table
     *
     * @param array
     */
    protected static $encodeTable = array(
        0  => 'a', 1  => 'b', 2  => 'c', 3  => 'd', 4  => 'e', 5  => 'f',
        6  => 'g', 7  => 'h', 8  => 'i', 9  => 'j', 10 => 'k', 11 => 'l',
        12 => 'm', 13 => 'n', 14 => 'o', 15 => 'p', 16 => 'q', 17 => 'r',
        18 => 's', 19 => 't', 20 => 'u', 21 => 'v', 22 => 'w', 23 => 'x',
        24 => 'y', 25 => 'z', 26 =>   0, 27 =>   1, 28 =>   2, 29 =>   3,
        30 =>   4, 31 =>   5, 32 =>   6, 33 =>   7, 34 =>   8, 35 =>   9,
    );

    /**
     * Decode table
     *
     * @param array
     */
    protected static $decodeTable = array(
        'a' =>  0, 'b' =>  1, 'c' =>  2, 'd' =>  3, 'e' =>  4, 'f' => 5,
        'g' =>  6, 'h' =>  7, 'i' =>  8, 'j' =>  9, 'k' => 10, 'l' => 11,
        'm' => 12, 'n' => 13, 'o' => 14, 'p' => 15, 'q' => 16, 'r' => 17,
        's' => 18, 't' => 19, 'u' => 20, 'v' => 21, 'w' => 22, 'x' => 23,
        'y' => 24, 'z' => 25,   0 => 26,   1 => 27,   2 => 28,   3 => 29,
          4 => 30,   5 => 31,   6 => 32,   7 => 33,   8 => 34,   9 => 35,
    );

    /**
     * Character encoding
     *
     * @param string
     */
    protected $encoding;

    /**
     * Constructor
     *
     * @param string $encoding Character encoding
     */
    public function __construct($encoding = 'UTF-8')
    {
        $this->encoding = $encoding;
    }

    /**
     * Encode a domain name to its Punycode version
     *
     * @param string $input Part of a domain name
     *
     * @return string Punycode representation in ASCII
     */
    public function encode($input)
    {
        return implode('.', array_map(array($this, 'encodeLabel'), explode('.', $input)));
    }

    /**
     * Decode a Punycode domain name to its Unicode counterpart
     *
     * @param string $input Domain name in Punycode
     *
     * @throws PunycodeException if a host label can not be decoded
     *
     * @return string Unicode domain name
     */
    public function decode($input)
    {
        return implode('.', array_map(array($this, 'decodeLabel'), explode('.', $input)));
    }

    /**
     * Encode a hostname label to its Punycode version
     *
     * @param string $input hostname label
     *
     * @return string hostname label punycode representation in ASCII
     */
    public function encodeLabel($input)
    {
        $codePoints = $this->codePoints($input);
        if (empty($codePoints['nonBasic'])) {
            return $input;
        }

        return $this->encodeString($codePoints, $input);
    }

    /**
     * Decode a Punycode hostname label to its Unicode counterpart
     *
     * @param string $input hostname label
     *
     * @return string Unicode hostname label
     */
    public function decodeLabel($input)
    {
        if (strpos($input, static::PREFIX) !== 0) {
            return $input;
        }

        $codePoints = $this->codePoints($input);
        if (! empty($codePoints["nonBasic"])) {
            return $input;
        }

        return $this->decodeString(substr($input, strlen(static::PREFIX)));
    }

    /**
     * List code points for a given input
     *
     * @param string $input
     *
     * @return array Multi-dimension array with basic, non-basic and aggregated code points
     */
    protected function codePoints($input)
    {
        $codePoints = array('all' => array(), 'basic' => array(), 'nonBasic' => array());
        $codePoints['all'] = array_map(
            array($this, 'charToCodePoint'),
            preg_split("//u", $input, -1, PREG_SPLIT_NO_EMPTY)
        );

        foreach ($codePoints['all'] as $code) {
            $codePoints[($code < 128) ? 'basic' : 'nonBasic'][] = $code;
        }

        $codePoints['nonBasic'] = array_unique($codePoints['nonBasic']);
        sort($codePoints['nonBasic']);

        return $codePoints;
    }

    /**
     * Convert a single or multi-byte character to its code point
     *
     * @param string $char
     *
     * @return int
     */
    protected function charToCodePoint($char)
    {
        $code = ord($char[0]);
        if ($code < 128) {
            return $code;
        }

        if ($code < 224) {
            return (($code - 192) * 64) + (ord($char[1]) - 128);
        }

        if ($code < 240) {
            return (($code - 224) * 4096) + ((ord($char[1]) - 128) * 64) + (ord($char[2]) - 128);
        }

        return (($code - 240) * 262144) + ((ord($char[1]) - 128) * 4096)
            + ((ord($char[2]) - 128) * 64) + (ord($char[3]) - 128);
    }

    /**
     * Encode a string into its punycode version
     *
     * @param  array  $codePoints input code points
     * @param  string $input      input string
     *
     * @return string
     */
    protected function encodeString(array $codePoints, $input)
    {
        $n      = static::INITIAL_N;
        $bias   = static::INITIAL_BIAS;
        $delta  = 0;
        $h      = count($codePoints['basic']);
        $b      = $h;
        $i      = 0;
        $length = mb_strlen($input, $this->encoding);
        $output = array_map(array($this, 'codePointToChar'), $codePoints['basic']);
        if ($b > 0) {
            $output[] = static::DELIMITER;
        }
        while ($h < $length) {
            $m     = $codePoints['nonBasic'][$i++];
            $delta = $delta + ($m - $n) * ($h + 1);
            $n     = $m;
            foreach ($codePoints['all'] as $c) {
                if ($c < $n || $c < static::INITIAL_N) {
                    ++$delta;
                }
                if ($c === $n) {
                    $q = $delta;
                    for ($k = static::BASE;; $k += static::BASE) {
                        $t = $this->calculateThreshold($k, $bias);
                        if ($q < $t) {
                            break;
                        }
                        $code     = $t + (($q - $t) % (static::BASE - $t));
                        $output[] = static::$encodeTable[$code];
                        $q        = ($q - $t) / (static::BASE - $t);
                    }
                    $output[] = static::$encodeTable[$q];
                    $bias     = $this->adapt($delta, $h + 1, $h === $b);
                    $delta    = 0;
                    $h++;
                }
            }
            ++$delta;
            ++$n;
        }

        return static::PREFIX.implode('', $output);
    }

    /**
     * Convert a code point to its single or multi-byte character
     *
     * @param int $code
     *
     * @return string
     */
    protected function codePointToChar($code)
    {
        if ($code <= 0x7F) {
            return chr($code);
        }

        if ($code <= 0x7FF) {
            return chr(($code >> 6) + 192).chr(($code & 63) + 128);
        }

        if ($code <= 0xFFFF) {
            return chr(($code >> 12) + 224).chr((($code >> 6) & 63) + 128).chr(($code & 63) + 128);
        }

        return chr(($code >> 18) + 240).chr((($code >> 12) & 63) + 128)
            .chr((($code >> 6) & 63) + 128).chr(($code & 63) + 128);
    }

    /**
     * Calculate the bias threshold to fall between TMIN and TMAX
     *
     * @param int $k
     * @param int $bias
     *
     * @return int
     */
    protected function calculateThreshold($k, $bias)
    {
        if ($k <= $bias + static::TMIN) {
            return static::TMIN;
        }

        if ($k >= $bias + static::TMAX) {
            return static::TMAX;
        }

        return $k - $bias;
    }

    /**
     * Bias adaptation
     *
     * @param int     $delta
     * @param int     $numPoints
     * @param boolean $firstTime
     *
     * @return int
     */
    protected function adapt($delta, $numPoints, $firstTime)
    {
        $offset = 0;
        $delta  = $firstTime ? floor($delta / static::DAMP) : $delta >> 1;
        $delta += floor($delta / $numPoints);

        $tmp = static::BASE - static::TMIN;
        for (; $delta > $tmp * static::TMAX >> 1; $offset += static::BASE) {
            $delta = floor($delta / $tmp);
        }

        return floor($offset + ($tmp + 1) * $delta / ($delta + static::SKEW));
    }

    /**
     * Decode a punycode encoded hostname label
     *
     * @param string $input the punycode encoded label
     *
     * @throws PunycodeException if a host label can not be decoded
     *
     * @return string|false Unicode hostname
     */
    protected function decodeString($input)
    {
        $output = array();
        $pos    = strrpos($input, static::DELIMITER);
        if ($pos !== false) {
            $output = str_split(substr($input, 0, $pos++));
        }
        $pos = (int) $pos;
        $outputLength = count($output);
        $inputLength  = strlen($input);
        $n    = static::INITIAL_N;
        $i    = 0;
        $bias = static::INITIAL_BIAS;
        while ($pos < $inputLength) {
            for ($oldi = $i, $w = 1, $k = static::BASE;; $k += static::BASE) {
                if ($pos >= $inputLength) {
                    throw new PunycodeException('the host label can not be decoded');
                }
                $digit = static::$decodeTable[$input[$pos++]];
                $i    += $digit * $w;
                $t     = $this->calculateThreshold($k, $bias);
                if ($digit < $t) {
                    break;
                }
                $w    *= (static::BASE - $t);
            }
            $bias = $this->adapt($i - $oldi, ++$outputLength, $oldi === 0);
            $n    = $n + floor($i / $outputLength);
            $i   %= $outputLength;
            $code = $this->codePointToChar($n);
            if (!mb_check_encoding($code, $this->encoding)) {
                throw new PunycodeException('the host label can not be decoded');
            }
            $output = array_merge(array_slice($output, 0, $i), array($code), array_slice($output, $i));
            $i++;
        }

        return implode('', $output);
    }
}
