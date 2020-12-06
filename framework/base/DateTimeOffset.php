<?php

declare(strict_types=1);
namespace hzfw\base;
use hzfw\core\BaseObject;
use DateInterval;
use DateTimeZone;
use DateTime;

class DateTimeOffset extends BaseObject
{
    // 常用格式
    const ATOM = "Y-m-d\TH:i:sP";
    const COOKIE = "l, d-M-Y H:i:s T";
    const ISO8601 = "Y-m-d\TH:i:sO";
    const RFC822 = "D, d M y H:i:s O";
    const RFC850 = "l, d-M-y H:i:s T";
    const RFC1036 = "D, d M y H:i:s O";
    const RFC1123 = "D, d M Y H:i:s O";
    const RFC7231 = "D, d M Y H:i:s \G\M\T";
    const RFC2822 = "D, d M Y H:i:s O";
    const RFC3339 = "Y-m-d\TH:i:sP";
    const RFC3339_EXTENDED = "Y-m-d\TH:i:s.vP";
    const RSS = "D, d M Y H:i:s O";
    const W3C = "Y-m-d\TH:i:sP";
    
    /**
     * 时区偏移
     * @var int
     */
    public int $offset;
    
    /**
     * 年
     * @var int
     */
    public int $year;
    
    /**
     * 月
     * @var int
     */
    public int $month;
    
    /**
     * 日
     * @var int
     */
    public int $day;
    
    /**
     * 时
     * @var int
     */
    public int $hour;
    
    /**
     * 分
     * @var int
     */
    public int $minute;
    
    /**
     * 秒
     * @var int
     */
    public int $second;
    
    /**
     * 微秒
     * @var int
     */
    public int $microSecond;
    
    /**
     * 一周中的一天
     * @var int
     */
    public int $dayOfWeek;
    
    /**
     * 一年中的一天
     * @var int
     */
    public int $dayOfYear;
    
    /**
     * 初始化
     * @param int $time 时间戳 秒
     * @param int $microSecond 微秒
     * @param int $offset 时区偏移 秒
     */
    public function __construct(int $time, ?int $microSecond = null, ?int $offset = null)
    {
        if (null === $microSecond)
        {
            $microSecond = 0;
        }
        
        if (null === $offset)
        {
            $date = new DateTime();
            $offset = $date->getOffset();
        }
        
        $date = new DateTime();
        $date->setTimestamp($time);
        $date->setTimezone(new DateTimeZone(self::OffsetToTimeZone($offset)));
        $arr = explode(" ", $date->format("Y m d H i s w z u P"));
        
        $this->offset = $offset;
        $this->microSecond = $microSecond;
        
        $this->year = (int)$arr[0];
        $this->month = (int)$arr[1];
        $this->day = (int)$arr[2];
        $this->hour = (int)$arr[3];
        $this->minute = (int)$arr[4];
        $this->second = (int)$arr[5];
        $this->dayOfWeek = (int)$arr[6];
        $this->dayOfYear = (int)$arr[7];
    }
    
    /**
     * 日期加减，返回新对象
     * @param int $years
     * @return self
     */
    public function AddYears(int $years): self
    {
        if ($years > 0)
        {
            return $this->Add("P{$years}Y");
        }
        else if ($years < 0)
        {
            return $this->Sub("P".(-$years)."Y");
        }
        
        return $this->ToOffset($this->offset);
    }
    
    /**
     * 日期加减，返回新对象
     * @param int $months
     * @return self
     */
    public function AddMonths(int $months): self
    {
        if ($months > 0)
        {
            return $this->Add("P{$months}M");
        }
        else if ($months < 0)
        {
            return $this->Sub("P".(-$months)."M");
        }
        
        return $this->ToOffset($this->offset);
    }
    
    /**
     * 日期加减，返回新对象
     * @param int $days
     * @return self
     */
    public function AddDays(int $days): self
    {
        if ($days > 0)
        {
            return $this->Add("P{$days}D");
        }
        else if ($days < 0)
        {
            return $this->Sub("P".(-$days)."D");
        }
        
        return $this->ToOffset($this->offset);
    }
    
    /**
     * 日期加减，返回新对象
     * @param int $hours
     * @return self
     */
    public function AddHours(int $hours): self
    {
        if ($hours > 0)
        {
            return $this->Add("PT{$hours}H");
        }
        else if ($hours < 0)
        {
            return $this->Sub("PT".(-$hours)."H");
        }
        
        return $this->ToOffset($this->offset);
    }
    
    /**
     * 日期加减，返回新对象
     * @param int $minutes
     * @return self
     */
    public function AddMinutes(int $minutes): self
    {
        if ($minutes > 0)
        {
            return $this->Add("PT{$minutes}M");
        }
        else if ($minutes < 0)
        {
            return $this->Sub("PT".(-$minutes)."M");
        }
        
        return $this->ToOffset($this->offset);
    }
    
    /**
     * 日期加减，返回新对象
     * @param int $seconds
     * @return self
     */
    public function AddSeconds (int $seconds): self
    {
        if ($seconds > 0)
        {
            return $this->Add("PT{$seconds}S");
        }
        else if ($seconds < 0)
        {
            return $this->Sub("PT".(-$seconds)."S");
        }
        
        return $this->ToOffset($this->offset);
    }
    
    /**
     * 日期加减，返回新对象
     * @param int $microSeconds
     * @return self
     */
    public function AddMicroSeconds (int $microSeconds): self
    {
        // 总微秒
        $microSeconds = $this->microSecond + $microSeconds;
        
        // 转换单位
        $absMicroSeconds = abs($microSeconds);
        $seconds = (int)($absMicroSeconds / 1000000);
        $micros = (int)($absMicroSeconds % 1000000);
        
        if ($microSeconds > 0)
        {
            $result = $this->Add("PT{$seconds}S");
            $result->microSecond = $micros;
            return $result;
        }
        else if ($microSeconds < 0)
        {
            $result = $this->Sub("PT".(-$seconds)."S");
            $result->microSecond = $micros;
            return $result;
        }
        
        return $this->ToOffset($this->offset);
    }
    
    /**
     * 日期加，返回新对象
     * @param string $spec
     * @return self
     */
    private function Add(string $spec): self
    {
        $date = self::DateTimeOffsetToDateTime($this)->add(new DateInterval($spec));
        return new self($date->getTimestamp(), $this->microSecond, $this->offset);
    }
    
    /**
     * 日期减，返回新对象
     * @param string $spec
     * @return self
     */
    private function Sub(string $spec): self
    {
        $date = self::DateTimeOffsetToDateTime($this)->sub(new DateInterval($spec));
        return new self($date->getTimestamp(), $this->microSecond, $this->offset);
    }
    
    /**
     * 时间间隔
     * @param self $other
     * @return DateInterval
     */
    public function DiffTo(self $other): DateInterval
    {
        $a = self::DateTimeOffsetToDateTime($this);
        $b = self::DateTimeOffsetToDateTime($other);
        return $a->diff($b);
    }
    
    /**
     * 到文本
     * @param string $format
     * @return string
     */
    public function ToString(string $format): string
    {
        $date = self::DateTimeOffsetToDateTime($this);
        $result = $date->format($format);
        return $result;
    }
    
    /**
     * 到时间戳
     * @return int
     */
    public function ToTimestamp(): int
    {
        $date = self::DateTimeOffsetToDateTime($this);
        $result = $date->getTimestamp();
        return $result;
    }
    
    /**
     * 到时间戳 毫秒
     * @return int
     */
    public function ToTimestampMillisecond(): int
    {
        $date = self::DateTimeOffsetToDateTime($this);
        $result = (int)(($date->getTimestamp() + ((float)$this->microSecond / 1000000)) * 1000);
        return $result;
    }
    
    /**
     * 到时间戳 微秒
     * @return int
     */
    public function ToTimestampMicrosecond(): int
    {
        $date = self::DateTimeOffsetToDateTime($this);
        $result = (int)(($date->getTimestamp() + ((float)$this->microSecond / 1000000)) * 1000000);
        return $result;
    }
    
    /**
     * 到偏移时间
     * @param int $offset
     * @return self
     */
    public function ToOffset(int $offset): self
    {
        return new self($this->ToTimestamp(), $this->microSecond, $offset);
    }
    
    /**
     * 比较大小
     * 返回时间差 秒
     * @param self $other
     * @return int
     */
    public function CompareTo(self $other): int
    {
        return $this->ToTimestamp() - $other->ToTimestamp();
    }
    
    /**
     * 比较大小
     * 返回时间差 毫秒
     * @param self $other
     * @return int
     */
    public function CompareMillisecondTo(self $other): int
    {
        return $this->ToTimestampMillisecond() - $other->ToTimestampMillisecond();
    }
    
    /**
     * 比较大小
     * 返回时间差 微秒
     * @param self $other
     * @return int
     */
    public function CompareMicrosecondTo(self $other): int
    {
        return $this->ToTimestampMicrosecond() - $other->ToTimestampMicrosecond();
    }
    
    /**
     * 当前UTC时间
     * @return DateTime
     */
    public static function UtcNow(): self
    {
        $time = microtime(true);
        return new self((int)$time, ((int)($time * 1000000)) % 1000000, 0);
    }
    
    /**
     * 当前时间
     * @return DateTime
     */
    public static function Now(): self
    {
        $time = microtime(true);
        return new self((int)$time, ((int)($time * 1000000)) % 1000000);
    }
    
    /**
     * 从时间戳创建
     * @param int $time
     * @param int $microSecond
     * @param int $offset
     * @return self
     */
    public static function FromTimestamp(int $time, ?int $microSecond = null, ?int $offset = null): self
    {
        return new self($time, $microSecond, $offset);
    }
    
    /**
     * 解析日期字符串
     * @param string $date
     * @return self
     */
    public static function Parse(string $date): self
    {
        $date = new DateTime($date);
        $microSecond = (int)$date->format("u");
        return new self($date->getTimestamp(), $microSecond, $date->getOffset());
    }
    
    /**
     * 比较大小
     * 返回时间差 秒
     * @param self $a
     * @param self $b
     * @return int
     */
    public static function Compare(self $a, self $b): int
    {
        return $a->CompareTo($b);
    }
    
    /**
     * 比较大小
     * 返回时间差 毫秒
     * @param self $a
     * @param self $b
     * @return int
     */
    public static function CompareMillisecond(self $a, self $b): int
    {
        return $a->CompareMillisecondTo($b);
    }
    
    /**
     * 比较大小
     * 返回时间差 毫秒
     * @param self $a
     * @param self $b
     * @return int
     */
    public static function CompareMicrosecond(self $a, self $b): int
    {
        return $a->CompareMicrosecondTo($b);
    }
    
    /**
     * 时间间隔
     * @param self $a
     * @param self $b
     * @return DateInterval
     */
    public static function Diff(self $a, self $b): DateInterval
    {
        return $a->DiffTo($b);
    }
    
    /**
     * 时区转偏移值
     * @param string $timezone
     * @return int
     */
    public static function TimeZoneToOffset(string $timezone): int
    {
        $dateTime = new DateTime(null, new DateTimeZone($timezone));
        return $dateTime->getOffset();
    }
    
    /**
     * 偏移值转时区
     * @param int $offset
     * @return string
     */
    public static function OffsetToTimeZone(int $offset): string
    {
        $absOffset = abs($offset);
        $h = (int)($absOffset / 3600);
        $m = (int)(($absOffset % 3600) / 60);
        return ($offset >=0 ? "+" : "-") . sprintf("%02d:%02d", $h, $m);
    }
    
    /**
     * 转换到 DateTime
     * @return DateTime
     */
    private static function DateTimeOffsetToDateTime(self $obj): DateTime
    {
        return new DateTime(
            sprintf("%02d-%02d-%02d %02d:%02d:%02d.%06d %s",
                $obj->year, $obj->month, $obj->day,
                $obj->hour, $obj->minute, $obj->second,
                $obj->microSecond, self::OffsetToTimeZone($obj->offset)));
    }
}
