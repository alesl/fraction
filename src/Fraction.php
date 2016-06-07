<?php

/*
 * This file is part of the Phospr Fraction package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phospr;

use InvalidArgumentException;
use Phospr\Exception\Fraction\InvalidDenominatorException;
use Phospr\Exception\Fraction\InvalidNumeratorException;

/**
 * Fraction
 *
 * Representation of a fraction, e.g. 3/4, 76/123, etc.
 *
 * @author Tom Haskins-Vaughan <tom@tomhv.uk>
 * @since  0.1.0
 */
class Fraction
{
    // Rounds away from zero
    const ROUND_UP         = 0;

    // Rounds towards zero
    const ROUND_DOWN       = 1;

    // Rounds towards Infinity
    const ROUND_CEIL       = 2;

    // Rounds towards -Infinity
    const ROUND_FLOOR      = 3;

    // Rounds towards nearest neighbour.
    // If equidistant, rounds away from zero
    const ROUND_HALF_UP    = 4;

    // Rounds towards nearest neighbour.
    // If equidistant, rounds towards zero
    const ROUND_HALF_DOWN  = 5;

    // Rounds towards nearest neighbour.
    // If equidistant, rounds towards even neighbour
    const ROUND_HALF_EVEN  = 6;

    // Rounds towards nearest neighbour.
    // If equidistant, rounds towards odd neighbour
    const ROUND_HALF_ODD   = 7;

    // Rounds towards nearest neighbour.
    // If equidistant, rounds towards Infinity
    const ROUND_HALF_CEIL  = 8;

    // Rounds towards nearest neighbour.
    // If equidistant, rounds towards -Infinity
    const ROUND_HALF_FLOOR = 9;

    /**
     * From string regex pattern
     *
     * @var string
     */
    const PATTERN_FROM_STRING = '#^(-?\d+)(?:(?: (\d+))?/(\d+))?$#';

    /**
     * numerator
     *
     * @var string
     */
    private $numerator;

    /**
     * denominator
     *
     * @var string
     */
    private $denominator;

    /**
     * __construct
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @param mixed $numerator (integer or integer string)
     * @param mixed $denominator (integer or integer string)
     */
    public function __construct($numerator, $denominator = 1)
    {
        if (!static::is_int($numerator)) {
            throw new InvalidNumeratorException(
                'Numerator must be an integer'
            );
        }

        if (!static::is_int($denominator)) {
            throw new InvalidDenominatorException(
                'Denominator must be an integer'
            );
        }

        $numerator = (string)$numerator;
        $denominator = (string)$denominator;

        if (static::cmp($denominator, '1') == -1) {
            throw new InvalidDenominatorException(
                'Denominator must be an integer greater than zero'
            );
        }

        if (0 == self::sgn($numerator)) {
            $this->numerator = '0';
            $this->denominator = '1';

            return;
        }

        $this->numerator = $numerator;
        $this->denominator = $denominator;

        $this->simplify();
    }

    /**
     * __toString
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->numerator === $this->denominator) {
            return '1';
        }

        if (bcmul('-1', $this->numerator, 0) === $this->denominator) {
            return '-1';
        }

        if (static::cmp('1', $this->denominator) == 0) {
            return (string) $this->numerator;
        }

        if (static::cmp(static::abs($this->numerator), static::abs($this->denominator)) == 1) {
            $whole = bcdiv(static::abs($this->numerator), $this->denominator, 0);

            if (static::sgn($this->numerator) == -1) {
                $whole = bcmul($whole, '-1', 0);
            }

            return sprintf('%s %s/%s',
                $whole,
                static::abs( bcmod($this->numerator, $this->denominator) ),
                $this->denominator
            );
        }

        return sprintf('%s/%s',
            $this->numerator,
            $this->denominator
        );
    }

    /**
     * Get numerator
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @return string
     */
    public function getNumerator()
    {
        return $this->numerator;
    }

    /**
     * Get denominator
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @return string
     */
    public function getDenominator()
    {
        return $this->denominator;
    }

    /**
     * Simplify
     *
     * e.g. transform 2/4 into 1/2
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @return void
     */
    private function simplify()
    {
        $gcd = $this->getGreatestCommonDivisor();

        $this->numerator = bcdiv($this->numerator, $gcd, 0);
        $this->denominator = bcdiv($this->denominator, $gcd, 0);
    }

    /**
     * Get greatest common divisor
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @return integer
     */
    private function getGreatestCommonDivisor()
    {
        return $this->calculateGreatestCommonDivisor($this->numerator, $this->denominator);
    }

    /**
     * Calculate greatest common divisor between two number
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @param string first value
     * @param string second value
     *
     * @return integer
     */
    private function calculateGreatestCommonDivisor($a, $b) {
        // ensure no negative values
        $a = static::abs($a);
        $b = static::abs($b);

        // ensure $a is greater than $b
        if (static::cmp($a, $b) == -1) {
            list($b, $a) = array($a, $b);
        }

        // see if $b is already the greatest common divisor
        $r = bcmod($a, $b);

        // if not, then keep trying
        while (static::sgn($r) == 1) {
            $a = $b;
            $b = $r;
            $r = bcmod($a, $b);
        }

        return $b;
    }

    /**
     * Multiply this fraction by a given fraction
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @param Fraction $fraction
     *
     * @return Fraction
     */
    public function multiply(Fraction $fraction)
    {
        $numerator = bcmul($this->getNumerator(), $fraction->getNumerator(), 0);
        $denominator = bcmul($this->getDenominator(), $fraction->getDenominator(), 0);

        return new static($numerator, $denominator);
    }

    /**
     * Divide this fraction by a given fraction
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @param Fraction $fraction
     *
     * @return Fraction
     */
    public function divide(Fraction $fraction)
    {
        $numerator = bcmul($this->getNumerator(), $fraction->getDenominator(), 0);
        $denominator = bcmul($this->getDenominator(), $fraction->getNumerator(), 0);

        return new static($numerator, $denominator);
    }

    /**
     * Add this fraction to a given fraction
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @param Fraction $fraction
     *
     * @return Fraction
     */
    public function add(Fraction $fraction)
    {
        $numerator = bcadd(
            bcmul($this->getNumerator(), $fraction->getDenominator(), 0),
            bcmul($fraction->getNumerator(), $this->getDenominator(), 0),
            0
        );

        $denominator = bcmul(
            $this->getDenominator(),
            $fraction->getDenominator(),
            0
        );

        return new static($numerator, $denominator);
    }

    /**
     * Subtract a given fraction from this fraction
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @param Fraction $fraction
     *
     * @return Fraction
     */
    public function subtract(Fraction $fraction)
    {
        $numerator = bcsub(
            bcmul($this->getNumerator(), $fraction->getDenominator(), 0),
            bcmul($fraction->getNumerator(), $this->getDenominator(), 0),
            0
        );

        $denominator = bcmul(
            $this->getDenominator(),
            $fraction->getDenominator(),
            0
        );

        return new static($numerator, $denominator);
    }

    /**
     * Whether or not this fraction is an integer
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @return boolean
     */
    public function isInteger()
    {
        return static::cmp('1', $this->getDenominator()) == 0;
    }

    /**
     * Create from float
     *
     * @author Christopher Tatro <c.m.tatro@gmail.com>
     * @since  0.2.0
     *
     * @param float $float
     *
     * return Fraction
     */
    public static function fromFloat($float)
    {
        if (is_float($float) || is_int($float)) {

        } else if (is_string($float)) {
          if (!preg_match('#^-?\d+(\.\d+)?$#', $float)) {
            throw new InvalidArgumentException(
                'Argument passed is not a numeric value.'
            );
          }
        } else {
          throw new InvalidArgumentException(
              'Argument passed is not a numeric value.'
          );
        }


        if (static::is_int($float)) {
            return new self($float);
        }

        // Make sure the float is a float not scientific notation.
        // Limit a max of 8 chars to prevent float errors
        // (this is only needed if argument is actually float)
        if (is_float($float)) {
          $float = rtrim(sprintf('%.8F', $float), 0);
        }

        // Find and grab the decimal space and everything after it
        $denominator = strstr($float, '.');

        // Pad a one with zeros for the length of the decimal places
        // ie  0.1 = 10; 0.02 = 100; 0.01234 = 100000;
        $denominator = str_pad('1', strlen($denominator), 0);
        // Multiply to get rid of the decimal places.
        $numerator = bcmul($float, $denominator, 0);

        return new self($numerator, $denominator);
    }

    /**
     * Create from string, e.g.
     *
     *     * 1/3
     *     * 1/20
     *     * 40
     *     * 3 4/5
     *     * 20 34/67
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.4.0
     *
     * @param string $string
     *
     * return Fraction
     */
    public static function fromString($string)
    {
        if (preg_match(self::PATTERN_FROM_STRING, trim($string), $matches)) {
            if (2 === count($matches)) {
                // whole number
                return new self((int) $matches[1]);
            } else {
                // either x y/z or x/y
                if ($matches[2]) {
                    // x y/z
                    $whole = new self((int) $matches[1]);

                    return $whole->add(new self(
                        (int) $matches[2],
                        (int) $matches[3]
                    ));
                }

                // x/y
                return new self((int) $matches[1], (int) $matches[3]);
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot parse "%s"',
            $string
        ));
    }

    /**
     * Get value as float
     *
     * @author Tom Haskins-Vaughan <tom@tomhv.uk>
     * @since  0.1.0
     *
     * @return float
     */
    public function toFloat()
    {
        return $this->getNumerator()/$this->getDenominator();
    }

    /**
     * isSameValueAs
     *
     * ValueObject comparison
     *
     * @author Christopher Tatro <c.m.tatro@gmail.com>
     * @since 1.1.0
     *
     * @param Fraction $fraction
     *
     * @return bool
     */
    public function isSameValueAs(Fraction $fraction)
    {
        if (self::cmp($this->getNumerator(), $fraction->getNumerator()) != 0) {
            return false;
        }

        if (self::cmp($this->getDenominator(), $fraction->getDenominator()) != 0) {
            return false;
        }

        return true;
    }

    /**
     * Number sign
     *
     * @param  string $value number
     * @return int        1 if number is positive, -1 if negative and 0 otherwiser
     */
    protected static function sgn($value) {
        if ($value==='0') {
            return 0;
        } else if ($value[0]==='-') {
              return -1;
        }

        return 1;
    }

    /**
     * Check if value is integer or integer string
     *
     * @param  mixed  $value
     * @return boolean
     */
    protected static function is_int($value) {
        return
            is_int($value)
            ||
            (
                is_string($value)
                &&
                !!preg_match('#^[+-]?\d+$#', (string)$value)
            )
        ;
    }

    /**
     * Compare two numbers
     *
     * @param  string $a
     * @param  string $b
     * @return integer  1 is $a>$b, -1 if $a<$b and 0 otherwise
     */
    protected static function cmp($a, $b) {
        return static::sgn(bcsub($a, $b, 0));
    }

    /**
     * Absolute value of number
     *
     * @param  string $value
     * @return string
     */
    protected static function abs($value) {
        if (static::sgn($value) == -1) {
            return substr($value, 1);
        }
        return $value;
    }

    /**
     * Compare two fractions
     *
     * @param  Fraction $fraction
     * @return integer 1 is $a>$b, -1 if $a<$b and 0 otherwise
     */
    public function compare(Fraction $fraction) {
        $a = $this->getDenominator();
        $b = $fraction->getDenominator();

        // calculate gcd
        $gcd = $this->calculateGreatestCommonDivisor($a, $b);

        // calculate lcm from gcd: GCD(a, b) * LCM(a, b) = a * b
        $lcm = bcdiv(bcmul($a, $b, 0), $gcd, 0);

        // compare numerators multiplied by lcm/denominator
        return static::cmp(
            bcmul($this->getNumerator(), bcdiv($lcm, $a, 0), 0),
            bcmul($fraction->getNumerator(), bcdiv($lcm, $b, 0), 0)
        );
    }

    /**
     * Is fraction greater than one from argument
     *
     * @param  Fraction $fraction
     * @return boolean
     */
    public function isGt(Fraction $fraction) {
        return $this->compare($fraction) == 1;
    }

    /**
     * Is fraction greater or equal than one from argument
     *
     * @param  Fraction $fraction
     * @return boolean
     */
    public function isGte(Fraction $fraction) {
        return $this->compare($fraction) > 0;
    }

    /**
     * Is fraction smaller than one from argument
     *
     * @param  Fraction $fraction
     * @return boolean
     */
    public function isLt(Fraction $fraction) {
        return $this->compare($fraction) == -1;
    }

    /**
     * Is fraction smaller or equal than one from argument
     *
     * @param  Fraction $fraction
     * @return boolean
     */
    public function isLte(Fraction $fraction) {
        return $this->compare($fraction) < 0;
    }

    /**
     * Are fractions equal
     *
     * @param  Fraction $fraction
     * @return boolean
     */
    public function isEq(Fraction $fraction) {
        return $this->compare($fraction) == 0;
    }

    /**
     * Get value as fixed point decimal
     * @param  integer $decimals precision
     * @return string
     */
    public function toFixed($decimals, $roundingMode = 4) {
      $numerator = $this->getNumerator();
      $denominator = $this->getDenominator();

      // calculate decimal with 1 more precision
      $value = bcdiv($numerator, $denominator, $decimals+1);
      $isPositive = static::sgn($value)>=0;

      // split to digits
      $digits = str_split($value, 1);

      // remove negative sign
      if (!$isPositive) {
        $digits = array_slice($digits, 1);
      }
      $decimalPos = array_search('.', $digits);

      // remove decimal point
      array_splice($digits, $decimalPos, 1);

      $extraDigit = array_pop($digits); // extra rounding digit
      $lastDigit = $digits[ sizeof($digits)-1 ];

      // if already 0, no rounding is needed
      if ($extraDigit==0) {
        return substr($value, 0, -1);
      }

      $roundNearest = function($extraDigit) {
        if ($extraDigit>5) {
          return 1;
        }
        return 0;
      };

      switch ($roundingMode) {
        // Rounds away from zero
        case static::ROUND_UP:
          $lastDigit++;
          break;

        // Rounds towards zero
        case static::ROUND_DOWN:
          break;

        // Rounds towards Infinity
        case static::ROUND_CEIL:
          if ($isPositive) {
            $lastDigit++;
          }
          break;

        // Rounds towards -Infinity
        case static::ROUND_FLOOR:
          if (!$isPositive) {
            $lastDigit++;
          }
          break;

        // Rounds towards nearest neighbour.
        // If equidistant, rounds away from zero
        case static::ROUND_HALF_UP:
          if ($extraDigit==5) {
            $lastDigit++;
          } else {
            $lastDigit += $roundNearest($extraDigit);
          }
          break;

        // Rounds towards nearest neighbour.
        // If equidistant, rounds towards zero
        case static::ROUND_HALF_DOWN:
          if ($extraDigit==5) {

          } else {
            $lastDigit += $roundNearest($extraDigit);
          }
          break;

        // Rounds towards nearest neighbour.
        // If equidistant, rounds towards even neighbour
        case static::ROUND_HALF_EVEN:
          if ($extraDigit==5) {
            if ($lastDigit%2==1) {
              $lastDigit++;
            }
          } else {
            $lastDigit += $roundNearest($extraDigit);
          }
          break;

        // Rounds towards nearest neighbour.
        // If equidistant, rounds towards odd neighbour
        case static::ROUND_HALF_ODD:
          if ($extraDigit==5) {
            if ($lastDigit%2==0) {
              $lastDigit++;
            }
          } else {
            $lastDigit += $roundNearest($extraDigit);
          }
          break;

        // Rounds towards nearest neighbour.
        // If equidistant, rounds towards Infinity
        case static::ROUND_HALF_CEIL:
          if ($extraDigit==5) {
            $lastDigit++;
          } else {
            $lastDigit += $roundNearest($extraDigit);
          }
          break;

        // Rounds towards nearest neighbour.
        // If equidistant, rounds towards -Infinity
        case static::ROUND_HALF_FLOOR:
          if ($extraDigit==5) {
            if (!$isPositive) {
              $lastDigit++;
            }
          } else {
            $lastDigit += $roundNearest($extraDigit);
          }
          break;

        default:
          return null;
      }

      $digits[ sizeof($digits)-1 ] = $lastDigit;

      // handle overflow
      $carry = 0;
      for ($ii=sizeof($digits)-1; $ii>=0; $ii--) {
        $digits[$ii] += $carry;
        if ($digits[$ii]>9) {
          $digits[$ii] = 0;
          $carry = 1;
        } else {
          $carry = 0;
        }
      }

      // add extra digit, if carry still exists
      if ($carry==1) {
        array_unshift($digits, 1);
        $decimalPos++;
      }

      // inject back decimal point
      if ($decimals>0) {
        array_splice($digits, $decimalPos, 0, ['.']);
      }

      $result = implode('', $digits);
      if (!$isPositive && $result!=='0') {
        $result = "-$result";
      }

      return $result;
    }
}
