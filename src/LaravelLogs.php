<?php

namespace MarceloCorrea\LaravelLogs;

class LaravelLogs
{
    private static $loggers = [];
    private        $spaces  = '';

    public static function channel($channel, $showDateTime = true)
    {
        $channels = (is_array($channel)) ? $channel : [$channel];
        foreach ($channels as $channel) {

            if ($channel == "exceptions") {
                continue;
            }

            if (\Config::get('laravel-logs.laravel-logs-active')) {
                try {
                    self::$loggers[] = self::makeLog($channel, $showDateTime);
                } catch (\Exception $e) {
                    \Log::error("L".__LINE__." > ".__METHOD__." Opss algo deu errado -> message `{$e->getMessage()}` - file `{$e->getFile()}` - line `{$e->getLine()}`");
                }
            }
        }

        return new self;
    }

    public static function database($type, $message = null, Array $options = [])
    {
        $log = array_merge([
            'user_id' => \Auth::user()->id ?? null,
            'type'    => $type,
            'message' => $message,
        ], $options);

        \Config::get('laravel-logs.model')::create($log);
    }

    public function info($message)
    {
        \Log::error($message);

        return $this;
    }

    public function debug($message)
    {
        if (count(self::$loggers)) {
            foreach (self::$loggers as $logger) {
                $logger->debug(is_array($message) ? json_encode($message) : $message);
            }
        }

        return $this;
    }

    public function dividingLine()
    {
        if (count(self::$loggers)) {
            foreach (self::$loggers as $logger) {
                $logger->debug("===============================================================================================================================================================");
            }
        }

        return $this;
    }

    public function tableVertical($data)
    {
        if ($data instanceof \Illuminate\Database\Eloquent\Model) {
            $data = $data->toArray();
        }

        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }

        if (is_array($data)) {

            $data = \Arr::dot($data);

            $line      = 0;
            $lineValue = 0;
            $newArr    = [];
            $totalLine = 0;
            foreach ($data as $key => $value) {

                $line = $line == 0 ? mb_strlen($key, 'utf8') : (mb_strlen($key, 'utf8') > $line ? mb_strlen($key,
                    'utf8') : $line);

                if (is_array($value)) {
                    $lineValue = 0;
                } elseif (is_object($value)) {
                    $lineValue = 0;
                } else {

                    $value     = $value ?? '-';
                    $lineValue = $lineValue == 0 ? mb_strlen($value, 'utf8') : (mb_strlen($value,
                        'utf8') > $lineValue ? mb_strlen($value, 'utf8') : $lineValue);
                }

                $newArr[$key] = $value;

                if (is_object($value)) {
                    continue;
                }
                if (is_object($key)) {
                    continue;
                }

                $val1 = ! is_array($key) ? str_pad($key, $line, ' ', STR_PAD_RIGHT) : null;
                $val2 = ! is_array($value) ? str_pad($value, $lineValue, ' ', STR_PAD_RIGHT) : null;

                $row = "|".$val1." | ".$val2." |";

                $totalLine = mb_strlen($row, 'utf8') > $totalLine ? mb_strlen($row, 'utf8') : $totalLine;
            }

            $strLine = '';

            if (count(self::$loggers)) {
                foreach (self::$loggers as $logger) {
                    foreach ($newArr as $key => $value) {

                        if (is_object($value)) {
                            continue;
                        }
                        if (is_object($key)) {
                            continue;
                        }

                        $val1 = ! is_array($key) ? str_pad($key, $line, ' ', STR_PAD_LEFT) : null;
                        $val2 = ! is_array($value) ? str_pad($value, $lineValue, ' ', STR_PAD_RIGHT) : null;

                        $row = "|".$val1." | ".$val2." |";

                        $strLine = 0;
                        $midLine = 0;
                        for ($i = 0; $i < $totalLine; $i++) {
                            $strLine .= "=";
                            $midLine .= "-";
                        }

                        $logger->debug($this->spaces.$strLine);
                        $logger->debug($this->spaces.$row);
                    }
                    $logger->debug($this->spaces.$strLine);
                    $logger->debug('');
                }
            }
        }
    }

    public function table($data)
    {
        if (! \Config::get('laravel-logs.laravel-logs-active')) {
            return $this;
        }

        if ($data instanceof \Illuminate\Database\Eloquent\Model) {
            $data = $data->toArray();
        }

        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }

        if (is_array($data) && count($data)) {

            $data = \Arr::dot($data);

            $line     = 0;
            $arrKey   = [];
            $arrValue = [];
            foreach ($data as $key => $value) {

                if (is_array($value)) {
                    continue;
                }
                if (is_object($value)) {
                    continue;
                }

                $len        = mb_strlen($key, 'utf8') > mb_strlen($value, 'utf8') ? mb_strlen($key,
                    'utf8') : mb_strlen($value, 'utf8');
                $line       += $len;
                $arrKey[]   = str_pad($key, $len, ' ', STR_PAD_RIGHT);
                $arrValue[] = str_pad($value, $len, ' ', STR_PAD_RIGHT);
            }

            $lineKey   = "| ".implode($arrKey, " | ")." |";
            $lineValue = "| ".implode($arrValue, " | ")." |";

            $strLine = '';
            for ($i = 0; $i < strlen($lineKey); $i++) {
                $strLine .= "=";
            }

            if (count(self::$loggers)) {
                foreach (self::$loggers as $logger) {
                    $logger->debug($this->spaces.$strLine);
                    $logger->debug($this->spaces.$lineKey);
                    $logger->debug($this->spaces.$strLine);
                    $logger->debug($this->spaces.$lineValue);
                    $logger->debug($this->spaces.$strLine);
                    $logger->debug('');
                }
            }
        }
    }

    public function update(Array $data)
    {
        $lenFields   = 0;
        $lenOriginal = 0;
        $lenChanges  = 0;

        foreach ($data as $field => $value) {
            $lenFields = mb_strlen($field, 'utf8') > $lenFields ? mb_strlen($field, 'utf8') : $lenFields;

            foreach ($value as $original => $changes) {
                $lenOriginal = mb_strlen($original, 'utf8') > $lenOriginal ? mb_strlen($original,
                    'utf8') : $lenOriginal;
                $lenChanges  = mb_strlen($changes, 'utf8') > $lenChanges ? mb_strlen($changes, 'utf8') : $lenChanges;
            }
        }

        if (count(self::$loggers)) {
            foreach (self::$loggers as $logger) {

                $strLine       = '';
                $strLineSingle = '';
                for ($i = 0; $i < ($lenFields + $lenOriginal + $lenChanges + 10); $i++) {
                    $strLine       .= "=";
                    $strLineSingle .= '-';
                }

                $logger->debug($strLine);
                $logger->debug("| ".str_pad("Campo", $lenFields, ' ', STR_PAD_RIGHT)." | ".str_pad("De", $lenOriginal,
                        ' ',
                        STR_PAD_RIGHT)." | ".str_pad("Para", $lenChanges, ' ', STR_PAD_RIGHT)." |");

                foreach ($data as $field => $value) {
                    foreach ($value as $original => $changes) {
                    }

                    $logger->debug($strLineSingle);

                    if (is_object($field)) {
                        continue;
                    }
                    if (is_object($original)) {
                        continue;
                    }
                    if (is_object($changes)) {
                        continue;
                    }
                    $fieldString    = str_pad($field, $lenFields, ' ', STR_PAD_RIGHT);
                    $originalString = str_pad($original,
                        strlen($original) - mb_strlen($original, 'UTF-8') + $lenOriginal,
                        ' ', STR_PAD_RIGHT);
                    $changesString  = str_pad($changes, strlen($changes) - mb_strlen($changes, 'UTF-8') + $lenChanges,
                        ' ',
                        STR_PAD_RIGHT);
                    $logger->debug("| {$fieldString} | {$originalString} | {$changesString} |");
                }

                $logger->debug($strLine);
            }
        }
    }

    /**
     * @param $channel
     *
     * @return \Monolog\Logger|mixed
     * @throws \Exception
     */
    private static function makeLog($channel, $showDateTime = true)
    {
        $output    = $showDateTime ? "%datetime% %message%\n" : "%message%\n";
        $formatter = new \Monolog\Formatter\LineFormatter($output);

        $date = (new \DateTime())->format('Y-m-d');
        $path = storage_path("logs/$date/$channel");
        $exp  = explode('/', $path);
        array_pop($exp);
        $path = implode('/', $exp);

        if (! \File::exists($path)) {
            \File::makeDirectory($path, 0755, true);
        }


        $stream = new \Monolog\Handler\StreamHandler(storage_path("logs/$date/$channel.log"), \Monolog\Logger::DEBUG);
        $stream->setFormatter($formatter);

        $loggerTimeZone = new \DateTimeZone('America/Sao_Paulo');
        $logger         = new \Monolog\Logger($channel);
        $logger->setTimezone($loggerTimeZone);
        $logger->pushHandler($stream);

        return $logger;
    }

    public static function console(String $message)
    {
        if (\Config::get('laravel-logs.laravel-logs-active')) {
            echo $message;
        }
    }
}
