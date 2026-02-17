<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notification_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'class',
        'parameters',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'parameters' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Log a notification to the database.
     *
     * @param  string  $notificationClass
     * @param  array  $parameters
     * @return self
     */
    public static function log(string $notificationClass, array $parameters = []): self
    {
        try {
            // Ensure parameters is a JSON-serializable array
            $parameters = is_array($parameters) ? $parameters : [];
            
            return static::create([
                'class' => $notificationClass,
                'parameters' => !empty($parameters) ? $parameters : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log notification', [
                'class' => $notificationClass,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Log a notification instance to the database.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return self
     */
    public static function logNotification(Notification $notification): self
    {
        $parameters = [];
        
        // Try to get properties using reflection
        try {
            $reflection = new \ReflectionClass($notification);
            
            // Get all properties, including protected and private ones
            $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);
            
            foreach ($properties as $property) {
                try {
                    // Make the property accessible
                    $property->setAccessible(true);
                    $value = $property->getValue($notification);
                    
                    // Only include scalar values, arrays, or objects that can be JSON encoded
                    if (is_scalar($value) || is_array($value) || (is_object($value) && method_exists($value, 'toArray'))) {
                        $parameters[$property->getName()] = is_object($value) && method_exists($value, 'toArray') 
                            ? $value->toArray() 
                            : $value;
                    }
                } catch (\Exception $e) {
                    // Skip properties that can't be accessed
                    continue;
                }
            }
            
            // If no parameters were found, try to get constructor parameters
            if (empty($parameters)) {
                $constructor = $reflection->getConstructor();
                if ($constructor) {
                    foreach ($constructor->getParameters() as $param) {
                        $paramName = $param->getName();
                        if ($reflection->hasProperty($paramName)) {
                            $property = $reflection->getProperty($paramName);
                            $property->setAccessible(true);
                            $parameters[$paramName] = $property->getValue($notification);
                        }
                    }
                }
            }
        } catch (\ReflectionException $e) {
            // If reflection fails, we'll just log the class name
            Log::warning('Could not reflect notification parameters', [
                'class' => get_class($notification),
                'error' => $e->getMessage(),
            ]);
        }

        return static::log(get_class($notification), $parameters);
    }

    /**
     * Get recent notifications of a specific class.
     *
     * @param  string  $notificationClass
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentForClass(string $notificationClass, int $limit = 10)
    {
        return static::where('class', $notificationClass)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if a notification was sent recently.
     *
     * @param  string  $notificationClass
     * @param  int  $minutes
     * @return bool
     */
    public static function wasSentRecently(string $notificationClass, int $minutes = 5): bool
    {
        return static::where('class', $notificationClass)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->exists();
    }
}
