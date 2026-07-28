<?php

namespace App\Support\Bpjs;

final class LzString
{
    private const URI_SAFE_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+-$';

    /**
     * Decompress the format produced by LZString.compressToEncodedURIComponent.
     */
    public static function decompressFromEncodedURIComponent(string $compressed): ?string
    {
        if ($compressed === '') {
            return '';
        }

        $compressed = str_replace(' ', '+', $compressed);

        return self::decompress(
            strlen($compressed),
            32,
            static function (int $index) use ($compressed): int {
                $value = strpos(self::URI_SAFE_ALPHABET, $compressed[$index]);

                return $value === false ? 0 : $value;
            }
        );
    }

    /**
     * This is a byte-safe PHP port of LZ-String's internal decompressor.
     * Dictionary entries are kept as UTF-16LE code units so surrogate pairs
     * survive until the final UTF-8 conversion.
     */
    private static function decompress(int $length, int $resetValue, callable $nextValue): ?string
    {
        $dictionary = [0, 1, 2];
        $enlargeIn = 4;
        $dictionarySize = 4;
        $numberOfBits = 3;
        $result = [];
        $dataValue = $nextValue(0);
        $dataPosition = $resetValue;
        $dataIndex = 1;

        $readBits = static function (int $bitCount) use (
            &$dataValue,
            &$dataPosition,
            &$dataIndex,
            $length,
            $resetValue,
            $nextValue
        ): ?int {
            $bits = 0;
            $power = 1;
            $maxPower = 1 << $bitCount;

            while ($power !== $maxPower) {
                $resultBit = $dataValue & $dataPosition;
                $dataPosition >>= 1;

                if ($dataPosition === 0) {
                    $dataPosition = $resetValue;

                    if ($dataIndex >= $length) {
                        $dataValue = 0;
                    } else {
                        $dataValue = $nextValue($dataIndex);
                    }

                    $dataIndex++;
                }

                if ($resultBit > 0) {
                    $bits |= $power;
                }

                $power <<= 1;
            }

            return $bits;
        };

        $next = $readBits(2);

        if ($next === 0) {
            $character = self::codeUnit($readBits(8) ?? 0);
        } elseif ($next === 1) {
            $character = self::codeUnit($readBits(16) ?? 0);
        } elseif ($next === 2) {
            return '';
        } else {
            return null;
        }

        $dictionary[3] = $character;
        $word = $character;
        $result[] = $character;

        while (true) {
            if ($dataIndex > $length) {
                return null;
            }

            $code = $readBits($numberOfBits);

            if ($code === 0) {
                $dictionary[$dictionarySize] = self::codeUnit($readBits(8) ?? 0);
                $code = $dictionarySize;
                $dictionarySize++;
                $enlargeIn--;
            } elseif ($code === 1) {
                $dictionary[$dictionarySize] = self::codeUnit($readBits(16) ?? 0);
                $code = $dictionarySize;
                $dictionarySize++;
                $enlargeIn--;
            } elseif ($code === 2) {
                return mb_convert_encoding(implode('', $result), 'UTF-8', 'UTF-16LE');
            }

            if ($enlargeIn === 0) {
                $enlargeIn = 1 << $numberOfBits;
                $numberOfBits++;
            }

            if (array_key_exists($code, $dictionary) && is_string($dictionary[$code])) {
                $entry = $dictionary[$code];
            } elseif ($code === $dictionarySize) {
                $entry = $word.self::firstCodeUnit($word);
            } else {
                return null;
            }

            $result[] = $entry;
            $dictionary[$dictionarySize] = $word.self::firstCodeUnit($entry);
            $dictionarySize++;
            $enlargeIn--;
            $word = $entry;

            if ($enlargeIn === 0) {
                $enlargeIn = 1 << $numberOfBits;
                $numberOfBits++;
            }
        }
    }

    private static function codeUnit(int $value): string
    {
        return pack('v', $value);
    }

    private static function firstCodeUnit(string $value): string
    {
        return substr($value, 0, 2);
    }
}
