<?php

namespace App\Http\Controllers;

use App\Models\NotificationStatus;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NotificationsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * List notification logs with time-based filtering
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $className
     * @return \Illuminate\Http\JsonResponse
     */
    public function listNotificationLogs(Request $request, string $className)
    {
        $request->validate([
            'time_filter' => 'sometimes|string|in:minute,5minutes,30minutes,hour,day,week,month',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        // Search for the class name anywhere in the full namespace
        $query = NotificationLog::where('class', 'LIKE', '%' . $className);

        // Apply time filter if provided
        $now = now();
        switch ($request->input('time_filter')) {
            case 'minute':
                $query->where('created_at', '>=', $now->copy()->subMinute());
                break;
            case '5minutes':
                $query->where('created_at', '>=', $now->copy()->subMinutes(5));
                break;
            case '30minutes':
                $query->where('created_at', '>=', $now->copy()->subMinutes(30));
                break;
            case 'hour':
                $query->where('created_at', '>=', $now->copy()->subHour());
                break;
            case 'day':
                $query->where('created_at', '>=', $now->copy()->subDay());
                break;
            case 'week':
                $query->where('created_at', '>=', $now->copy()->subWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', $now->copy()->subMonth());
                break;
        }

        $perPage = $request->input('per_page', 20);
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Toggle notification status
     *
     * @param Request $request
     * @param string $notificationClass
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleNotificationStatus(Request $request, $notificationClass)
    {
        $request->validate([
            'is_active' => 'sometimes|boolean'
        ]);

        $decodedClass = str_replace('_', '\\', $notificationClass);
        
        // Find or create the notification status
        $notification = \App\Models\NotificationStatus::firstOrNew(
            ['notification_class' => $decodedClass],
            ['is_active' => true]
        );

        // Toggle status if no specific status provided, otherwise use the provided one
        $isActive = $request->has('is_active') 
            ? $request->boolean('is_active')
            : !$notification->is_active;

        $notification->is_active = $isActive;
        $notification->save();

        return response()->json([
            'message' => 'Notification status updated successfully',
            'notification' => $notification
        ]);
    }

    /**
     * Map of notification classes to their schedule times
     * This should match the schedule in app/Console/Kernel.php
     */
    protected $notificationSchedules = [
        'App\\Tasks\\SendFlightManifestReminders' => '10:00',
        'App\\Tasks\\FinalFlightManifestReminders' => '10:00',
        'App\\Tasks\\LastFlightManifestReminders' => '10:00',
        'App\\Tasks\\BalanceDueDateReminders' => '12:00',
        'App\\Tasks\\CancellationsLastCalls' => '14:00',
        'App\\Tasks\\PaymentDueDateReminders' => '16:00',
        'App\\Tasks\\FinalEmail' => '18:00',
        'App\\Tasks\\NonConfirmedBookingWithConfirmedPayment' => '20:00',
    ];

    /**
     * Calculate the next run time for a notification
     * 
     * @param string $className
     * @return string|null
     */
    protected function getNextRunTime($className)
    {
        // $baseClassName = class_basename($className);
        // $taskClass = 'App\\Tasks\\' . str_replace('Notification', '', $baseClassName);
        
        // if (isset($this->notificationSchedules[$taskClass])) {
        //     $scheduleTime = $this->notificationSchedules[$taskClass];
        //     $now = now();
        //     $today = $now->format('Y-m-d');
        //     $scheduledTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', "$today $scheduleTime");
            
        //     // If the scheduled time has already passed today, set it for tomorrow
        //     if ($scheduledTime->lt($now)) {
        //         $scheduledTime->addDay();
        //     }
            
        //     return $scheduledTime->format('Y-m-d H:i');
        // }
        
        // return null;

        switch ($className) {
            case 'App\\Notifications\\BalanceDueDateReminder':
                return '2 Weeks before Balance Due Date';

            case 'App\\Notifications\\CancellationsLastCalls':
                return '2 Weeks before Cancellation Date';

            case 'App\\Notifications\\FinalEmail':
                return '7 Days before Departure Date';

            case 'App\\Notifications\\FinalFlightManifestReminder':
                return 'Flight Manifest Submission was yesterday';

            case 'App\\Notifications\\FitQuoteReminder':
                return 'Not accepted fit quotes after 7, 10 and 11 days';

            case 'App\\Notifications\\FlightManifestRequest':
                return 'Flight Manifest Submission is in 60, 30, 14, 5 or 1 day from today';

            case 'App\\Notifications\\LastFlightManifestReminder':
                return 'Missing Flight Manifests before 30 days of the event date';

            case 'App\\Notifications\\NonConfirmedBookingWithConfirmedPayment':
                return 'Bookings are not confirmed with at leat 1 confirmed payment';

            case 'App\\Notifications\\PaymentDueDateReminder':
                return 'Bookings for incomplete payments 2 weeks before due date';

            default:
                return null;
        }
    }

    /**
     * Display a listing of all available notification classes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Get all notification statuses in one query
        $notificationStatuses = NotificationStatus::get()
            ->keyBy('notification_class');
        
        $excludedClasses = [
            'App\\Notifications\\BaseNotification',
            'App\\Notifications\\BookingInvoice',
            'App\\Notifications\\BookingInvoiceFinal',
            'App\\Notifications\\BookingReservationCodeNotification',
            'App\\Notifications\\BookingSubmitted',
            'App\\Notifications\\BookingSubmittedReservationCode',
            'App\\Notifications\\BookingSubmittedReservationCodeSeperateInvoice',
            'App\\Notifications\\BrideGroupEmail',
            'App\\Notifications\\CardDeclined',
            'App\\Notifications\\FitClientPaymentReminder',
            'App\\Notifications\\FitQuoteMail',
            'App\\Notifications\\FitQuoteAccepted',
            'App\\Notifications\\FitQuoteCancelled',
            'App\\Notifications\\FlightManifestSubmitted',
            'App\\Notifications\\InvoiceMail',
            'App\\Notifications\\OtpNotification',
            'App\\Notifications\\PasswordChanged',
            'App\\Notifications\\PaymentConfirmed',
            'App\\Notifications\\PaymentInformationUpdatedNotification',
            'App\\Notifications\\PaymentSubmitted',
            'App\\Notifications\\UserCreated',
            'App\\Notifications\\PasswordReset',
            'App\\Notifications\\GroupPasswordNotification',
            'App\\Notifications\\SendCouplesSitePasswordNotification',
            'App\\Notifications\\SendGroupPasswordNotification',
            'App\\Notifications\\GroupEmail',
            'App\\Notifications\\TravelDocumentsMail'
        ];

        $notifications = collect(File::files(app_path('Notifications')))
            ->map(function ($file) use ($notificationStatuses, $excludedClasses) {
                $className = 'App\\Notifications\\' . $file->getBasename('.php');
                
                // Excluded classes, and non-existent classes
                if (in_array($className, $excludedClasses) || 
                    !class_exists($className)) {
                    return null;
                }
                
                // Convert class name to a more readable format
                $name = (string) Str::of($file->getBasename('.php'))
                    ->replace('Notification', '')
                    ->kebab()
                    ->replace('-', ' ')
                    ->title();

                $query = NotificationStatus::where('notification_class', $className);
                
                // Check if notification is active
                $isActive = !$query->exists() || true == $query->first()->is_active ? true : false;
                
                // Get next run time
                $nextRun = $this->getNextRunTime($className);
                
                return [
                    'name' => $name,
                    'class' => $className,
                    'short_class' => $file->getBasename('.php'),
                    'is_active' => $isActive,
                    'next_run' => $nextRun
                ];
            })
            ->filter()
            ->sortBy('name')
            ->values()
            ->all();
        
        return response()->json([
            'data' => $notifications,
            'meta' => [
                'total' => count($notifications),
            ],
            'can' => [
                'create' => false,
                'update' => true,
                'delete' => false,
            ]
        ]);
    }

    /**
     * Display an HTML preview of the specified notification.
     *
     * @param  string  $className
     * @return \Illuminate\Http\Response
     */
    public function preview($className)
    {
        // Decode the URL-encoded class name
        $className = urldecode($className);
        
        // Validate the class exists and is a notification
        if (!class_exists($className) || !is_subclass_of($className, '\Illuminate\Notifications\Notification')) {
            abort(404, 'Notification class not found');
        }

        try {
            // Get constructor parameters for this notification class
            $constructorParams = $this->getDataForNotification($className);
            
            if (empty($constructorParams)) {
                throw new \Exception("No data configured for notification: {$className}");
            }
            
            // Create notification instance with the correct parameters
            $reflection = new \ReflectionClass($className);
            $notification = $reflection->newInstanceArgs($constructorParams);
            
            // Get the appropriate notifiable entity for this notification
            $notifiable = $this->getNotifiable($className);

            // Get the mail message
            $message = $notification->toMail($notifiable);
            
            // Render the HTML
            return response($message->render());
            
        } catch (\Exception $e) {
            \Log::error('Error generating notification preview: ' . $e->getMessage(), [
                'exception' => $e,
                'notification' => $className
            ]);
            
            return response()->json([
                'error' => 'Could not generate preview',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for BalanceDueDateReminder notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getBalanceDueDateReminderNotifiable()
    {
        // Find a client with a booking that has guests
        return  \App\Models\Client::whereHas('bookings', function($q) {
                $q->whereHas('guests');
            })
            ->with(['bookings.guests', 'bookings.booking.group'])
            ->first();
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for BookingInvoice notification
     * 
     * @return \App\Models\BookingClient
     * @throws \Exception
     */
    protected function getBookingInvoiceNotifiable()
    {
        // First try to find a booking client with all required relationships and a reservation code
        return \App\Models\BookingClient::with([
                'client',
                'booking.group',
                'booking.clients',
                'guests',
                'booking.payments'
            ])
            ->whereNotNull('reservation_code')
            ->whereHas('booking', function($q) {
                $q->whereHas('group')
                  ->whereHas('clients')
                  ->whereHas('payments');
            })
            ->whereHas('client')
            ->first();
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for BookingInvoiceFinal notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getBookingInvoiceFinalNotifiable()
    {
        // Find a booking client with reservation code and payments
        return \App\Models\BookingClient::with([
                'client', 
                'booking.group', 
                'booking.payments'
            ])
            ->whereNotNull('reservation_code')
            ->whereHas('booking', function($q) {
                $q->whereHas('group')
                  ->whereHas('payments');
            })
            ->whereHas('client')
            ->first();
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for BookingReservationCodeNotification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getBookingReservationCodeNotificationNotifiable()
    {
        // Find a booking client with a valid reservation code
        $bookingClient = \App\Models\BookingClient::with([
                'client',
                'booking.group',
                'guests'
            ])
            ->whereNotNull('reservation_code')
            ->whereHas('booking.group')
            ->whereHas('client')
            ->inRandomOrder()
            ->first();

        if ($bookingClient) {
            return $bookingClient->client;
        }

        // Fallback to any client with a booking
        $client = \App\Models\Client::whereHas('bookingClients', function($q) {
                $q->whereHas('booking.group');
            })
            ->with(['bookingClients.booking.group'])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            throw new \Exception('No suitable client found for reservation code notification');
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for BookingSubmitted notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getBookingSubmittedNotifiable()
    {
        // Find a client with a recently submitted booking
        $client = \App\Models\Client::whereHas('bookingClients.booking', function($q) {
                $q->where('created_at', '>=', now()->subDays(30)) // Bookings from the last 30 days
                  ->whereHas('group');
            })
            ->with(['bookingClients.booking.group'])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a booking
            $client = \App\Models\Client::whereHas('bookingClients', function($q) {
                    $q->whereHas('booking.group');
                })
                ->with(['bookingClients.booking.group'])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                throw new \Exception('No clients with recent bookings found');
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for BookingSubmittedReservationCode notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getBookingSubmittedReservationCodeNotifiable()
    {
        // Find a client with a recently submitted booking that has a reservation code
        $client = \App\Models\Client::whereHas('bookingClients', function($q) {
                $q->whereNotNull('reservation_code')
                  ->whereHas('booking', function($q) {
                      $q->where('created_at', '>=', now()->subDays(30)) // Recent bookings
                        ->whereHas('group');
                  });
            })
            ->with(['bookingClients' => function($q) {
                $q->whereNotNull('reservation_code')
                  ->with(['booking.group']);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a reservation code
            return $this->getBookingReservationCodeNotificationNotifiable();
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for BookingSubmittedReservationCodeSeperateInvoice notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getBookingSubmittedReservationCodeSeperateInvoiceNotifiable()
    {
        // Find a client with a booking that has a reservation code and separate invoice
        $client = \App\Models\Client::whereHas('bookingClients', function($q) {
                $q->whereNotNull('reservation_code')
                  ->whereHas('booking', function($q) {
                      $q->where('has_separate_invoice', true)
                        ->whereHas('group')
                        ->where('created_at', '>=', now()->subDays(30)); // Recent bookings
                  });
            })
            ->with(['bookingClients' => function($q) {
                $q->whereNotNull('reservation_code')
                  ->with(['booking.group']);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a reservation code if no separate invoice found
            return $this->getBookingSubmittedReservationCodeNotifiable();
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for BrideGroupEmail notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getBrideGroupEmailNotifiable()
    {
        // Find a client who is a bride with an active booking group
        $client = \App\Models\Client::where('is_bride', true)
            ->whereHas('bookingClients.booking.group', function($q) {
                $q->where('is_active', true)
                  ->where('event_date', '>=', now()->subYear()); // Groups with events in the past year
            })
            ->with(['bookingClients.booking.group'])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client marked as a bride
            $client = \App\Models\Client::where('is_bride', true)
                ->with(['bookingClients.booking.group'])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any client with a booking
                return $this->getBookingSubmittedNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for CancellationsLastCalls notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getCancellationsLastCallsNotifiable()
    {
        return \App\Models\BookingClient::with(
            ['client'])
            ->whereHas('booking.group', function($query) {
                $query->whereNotNull('cancellation_date');
            })
            ->first()->client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for CardDeclined notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getCardDeclinedNotifiable()
    {
        // Find a client with a recent declined payment
        $client = \App\Models\Client::whereHas('payments', function($q) {
                $q->where('status', 'declined')
                  ->where('created_at', '>=', now()->subDays(7)); // Last 7 days
            })
            ->with(['payments' => function($q) {
                $q->where('status', 'declined')
                  ->orderBy('created_at', 'desc')
                  ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with any payment attempt
            $client = \App\Models\Client::whereHas('payments')
                ->with(['payments' => function($q) {
                    $q->orderBy('created_at', 'desc')
                      ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any client with a booking
                return $this->getBookingSubmittedNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for FinalEmail notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getFinalEmailNotifiable()
    {
        return \App\Models\Booking::with(['guests'])
            ->whereHas('guests.booking_client.client')
            ->first()->guests->first()->booking_client->client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for FinalFlightManifestReminder notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getFinalFlightManifestReminderNotifiable()
    {
        return \App\Models\BookingClient::with([
            'booking.clients'
        ])
        ->first();
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for FitClientPaymentReminder notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getFitClientPaymentReminderNotifiable()
    {
        // Find FIT clients with pending payments
        $client = \App\Models\Client::where('is_fit', true)
            ->whereHas('bookingClients.booking', function($q) {
                $q->whereHas('payments', function($q) {
                    $q->where('status', 'pending')
                      ->where('due_date', '<=', now()->addDays(7)); // Payment due within 7 days
                });
            })
            ->with(['bookingClients.booking.payments' => function($q) {
                $q->where('status', 'pending')
                  ->orderBy('due_date', 'asc');
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any FIT client with a pending payment
            $client = \App\Models\Client::where('is_fit', true)
                ->whereHas('bookingClients.booking.payments', function($q) {
                    $q->where('status', 'pending');
                })
                ->with(['bookingClients.booking.payments' => function($q) {
                    $q->where('status', 'pending')
                      ->orderBy('due_date', 'asc');
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any FIT client
                $client = \App\Models\Client::where('is_fit', true)
                    ->inRandomOrder()
                    ->first();

                if (!$client) {
                    // Ultimate fallback to any client with a booking
                    return $this->getBookingSubmittedNotifiable();
                }
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for FitQuoteAccepted notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getFitQuoteAcceptedNotifiable()
    {
        // Find clients with recently accepted FIT quotes
        $client = \App\Models\Client::where('is_fit', true)
            ->whereHas('quotes', function($q) {
                $q->where('status', 'accepted')
                  ->where('accepted_at', '>=', now()->subDays(7)); // Accepted in the last 7 days
            })
            ->with(['quotes' => function($q) {
                $q->where('status', 'accepted')
                  ->orderBy('accepted_at', 'desc')
                  ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with an accepted quote
            $client = \App\Models\Client::whereHas('quotes', function($q) {
                    $q->where('status', 'accepted');
                })
                ->with(['quotes' => function($q) {
                    $q->where('status', 'accepted')
                      ->orderBy('accepted_at', 'desc')
                      ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any FIT client
                $client = \App\Models\Client::where('is_fit', true)
                    ->inRandomOrder()
                    ->first();

                if (!$client) {
                    // Ultimate fallback to any client with a booking
                    return $this->getBookingSubmittedNotifiable();
                }
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for FitQuoteCancelled notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getFitQuoteCancelledNotifiable()
    {
        // Find clients with recently cancelled FIT quotes
        $client = \App\Models\Client::where('is_fit', true)
            ->whereHas('quotes', function($q) {
                $q->where('status', 'cancelled')
                  ->where('cancelled_at', '>=', now()->subDays(7)); // Cancelled in the last 7 days
            })
            ->with(['quotes' => function($q) {
                $q->where('status', 'cancelled')
                  ->orderBy('cancelled_at', 'desc')
                  ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a cancelled quote
            $client = \App\Models\Client::whereHas('quotes', function($q) {
                    $q->where('status', 'cancelled');
                })
                ->with(['quotes' => function($q) {
                    $q->where('status', 'cancelled')
                      ->orderBy('cancelled_at', 'desc')
                      ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any FIT client
                return $this->getFitQuoteAcceptedNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for FitQuoteMail notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getFitQuoteMailNotifiable()
    {
        // Find FIT clients with recent quote requests
        $client = \App\Models\Client::where('is_fit', true)
            ->whereHas('quotes', function($q) {
                $q->where('status', 'pending')
                  ->where('created_at', '>=', now()->subDays(7)); // Requested in the last 7 days
            })
            ->with(['quotes' => function($q) {
                $q->where('status', 'pending')
                  ->orderBy('created_at', 'desc')
                  ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a pending quote
            $client = \App\Models\Client::whereHas('quotes', function($q) {
                    $q->where('status', 'pending');
                })
                ->with(['quotes' => function($q) {
                    $q->where('status', 'pending')
                      ->orderBy('created_at', 'desc')
                      ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any FIT client
                return $this->getFitQuoteAcceptedNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for FitQuoteReminder notification
     * 
     * @return \App\Models\Client|null
     * @throws \Exception
     */
    protected function getFitQuoteReminderNotifiable()
    {
        $client = \App\Models\Client::whereHas('bookings', function($query) {
                $query->whereHas('fitQuotes');
            })
            ->with('bookings.fitQuotes')
            ->first();

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for FlightManifestRequest notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getFlightManifestRequestNotifiable()
    {
        $bookingClient = \App\Models\BookingClient::with([
            'booking.clients',
        ])
        ->whereHas('guests')
        ->whereHas('booking.group', function($q) {
            $q->whereNotNull('transportation_submit_before');
        })
        ->first();

        return $bookingClient->client;       
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for FlightManifestSubmitted notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getFlightManifestSubmittedNotifiable()
    {
        // Find clients who recently submitted their flight manifest
        $client = \App\Models\Client::whereHas('bookingClients.booking.group', function($q) {
                $q->where('manifest_submitted_at', '>=', now()->subDay()) // Submitted in the last 24 hours
                  ->whereNotNull('manifest_submitted_at');
            })
            ->with(['bookingClients.booking.group' => function($q) {
                $q->where('manifest_submitted_at', '>=', now()->subDay())
                  ->orderBy('manifest_submitted_at', 'desc')
                  ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a submitted manifest
            $client = \App\Models\Client::whereHas('bookingClients.booking.group', function($q) {
                    $q->whereNotNull('manifest_submitted_at');
                })
                ->with(['bookingClients.booking.group' => function($q) {
                    $q->whereNotNull('manifest_submitted_at')
                      ->orderBy('manifest_submitted_at', 'desc')
                      ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any client with an upcoming flight
                return $this->getFlightManifestRequestNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for GroupEmail notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getGroupEmailNotifiable()
    {
        // Find clients who are part of a group booking
        $client = \App\Models\Client::whereHas('bookingClients.booking.group', function($q) {
                $q->where('is_active', true)
                  ->where('event_date', '>=', now()->subDays(90)); // Groups with events in the last 90 days
            })
            ->with(['bookingClients.booking.group'])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a group booking
            $client = \App\Models\Client::whereHas('bookingClients.booking.group')
                ->with(['bookingClients.booking.group'])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any client with a booking
                return $this->getBookingSubmittedNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for GroupPasswordNotification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getGroupPasswordNotificationNotifiable()
    {
        // Find clients who are part of a group with password protection
        $client = \App\Models\Client::whereHas('bookingClients.booking.group', function($q) {
                $q->whereNotNull('password')
                  ->where('is_active', true)
                  ->where('event_date', '>=', now()->subDays(30)); // Active groups in the last 30 days
            })
            ->with(['bookingClients.booking.group'])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a group that has a password
            $client = \App\Models\Client::whereHas('bookingClients.booking.group', function($q) {
                    $q->whereNotNull('password');
                })
                ->with(['bookingClients.booking.group'])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any client with a group booking
                return $this->getGroupEmailNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for InvoiceMail notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getInvoiceMailNotifiable()
    {
        // Find clients with recent invoices
        $client = \App\Models\Client::whereHas('invoices', function($q) {
                $q->where('created_at', '>=', now()->subDays(7)) // Invoices from the last 7 days
                  ->whereNull('cancelled_at');
            })
            ->with(['invoices' => function($q) {
                $q->where('created_at', '>=', now()->subDays(7))
                  ->orderBy('created_at', 'desc')
                  ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with an invoice
            $client = \App\Models\Client::whereHas('invoices')
                ->with(['invoices' => function($q) {
                    $q->orderBy('created_at', 'desc')
                      ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any client with a booking
                return $this->getBookingSubmittedNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for LastFlightManifestReminder notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getLastFlightManifestReminderNotifiable()
    {
        return \App\Models\BookingClient::with([
            'booking.clients',
            'booking.group',
        ])
        ->whereHas('booking.group', function($q) {
            $q->whereNotNull('transportation_submit_before');
        })
        ->first();
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for NonConfirmedBookingWithConfirmedPayment
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getNonConfirmedBookingWithConfirmedPaymentNotifiable()
    {
        $booking = \App\Models\Booking::with('clients.client')
        ->first();
        
        return $booking->clients->first()->client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for OtpNotification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getOtpNotificationNotifiable()
    {
        // Find clients who have logged in recently (potential OTP recipients)
        $client = \App\Models\Client::whereNotNull('last_login_at')
            ->where('last_login_at', '>=', now()->subHour()) // Logged in within the last hour
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a recent activity
            $client = \App\Models\Client::whereHas('activities', function($q) {
                    $q->where('created_at', '>=', now()->subDay());
                })
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any active client
                $client = \App\Models\Client::where('is_active', true)
                    ->inRandomOrder()
                    ->first();

                if (!$client) {
                    throw new \Exception('No suitable client found for OTP notification');
                }
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for PasswordChanged notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getPasswordChangedNotifiable()
    {
        // Find clients who have recently changed their password
        $client = \App\Models\Client::whereNotNull('password_changed_at')
            ->where('password_changed_at', '>=', now()->subHour()) // Changed within the last hour
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a recent login
            $client = \App\Models\Client::whereNotNull('last_login_at')
                ->where('last_login_at', '>=', now()->subDay())
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any active client
                $client = \App\Models\Client::where('is_active', true)
                    ->inRandomOrder()
                    ->first();

                if (!$client) {
                    throw new \Exception('No suitable client found for password changed notification');
                }
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for PasswordReset notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getPasswordResetNotifiable()
    {
        // Find clients who have recently requested a password reset
        $client = \App\Models\Client::whereHas('passwordResetTokens', function($q) {
                $q->where('created_at', '>=', now()->subHour()); // Reset requested in last hour
            })
            ->with(['passwordResetTokens' => function($q) {
                $q->orderBy('created_at', 'desc')
                  ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a password reset token
            $client = \App\Models\Client::whereHas('passwordResetTokens')
                ->with(['passwordResetTokens' => function($q) {
                    $q->orderBy('created_at', 'desc')
                      ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any active client with an email
                $client = \App\Models\Client::where('is_active', true)
                    ->whereNotNull('email')
                    ->inRandomOrder()
                    ->first();

                if (!$client) {
                    throw new \Exception('No suitable client found for password reset notification');
                }
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for PaymentConfirmed notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getPaymentConfirmedNotifiable()
    {
        // Find clients with recently confirmed payments
        $client = \App\Models\Client::whereHas('bookingClients.booking.payments', function($q) {
                $q->where('status', 'confirmed')
                  ->where('confirmed_at', '>=', now()->subHour()); // Confirmed in last hour
            })
            ->with(['bookingClients.booking.payments' => function($q) {
                $q->where('status', 'confirmed')
                  ->orderBy('confirmed_at', 'desc')
                  ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a confirmed payment
            $client = \App\Models\Client::whereHas('bookingClients.booking.payments', function($q) {
                    $q->where('status', 'confirmed');
                })
                ->with(['bookingClients.booking.payments' => function($q) {
                    $q->where('status', 'confirmed')
                      ->orderBy('confirmed_at', 'desc')
                      ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any client with a booking
                return $this->getBookingSubmittedNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for PaymentDueDateReminder notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getPaymentDueDateReminderNotifiable()
    {
        return \App\Models\Client::first();
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for PaymentInformationUpdatedNotification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getPaymentInformationUpdatedNotificationNotifiable()
    {
        // Find clients who have recently updated their payment information
        $client = \App\Models\Client::whereHas('paymentMethods', function($q) {
                $q->where('updated_at', '>=', now()->subHour()); // Updated in last hour
            })
            ->with(['paymentMethods' => function($q) {
                $q->orderBy('updated_at', 'desc')
                  ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a payment method
            $client = \App\Models\Client::whereHas('paymentMethods')
                ->with(['paymentMethods' => function($q) {
                    $q->orderBy('updated_at', 'desc')
                      ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any client with a recent booking
                return $this->getBookingSubmittedNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for PaymentSubmitted notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getPaymentSubmittedNotifiable()
    {
        // Find clients with payments submitted in the last hour
        $client = \App\Models\Client::whereHas('bookingClients.booking.payments', function($q) {
                $q->where('status', 'submitted')
                  ->where('created_at', '>=', now()->subHour());
            })
            ->with(['bookingClients.booking.payments' => function($q) {
                $q->where('status', 'submitted')
                  ->orderBy('created_at', 'desc')
                  ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a submitted payment
            $client = \App\Models\Client::whereHas('bookingClients.booking.payments', function($q) {
                    $q->where('status', 'submitted');
                })
                ->with(['bookingClients.booking.payments' => function($q) {
                    $q->where('status', 'submitted')
                      ->orderBy('created_at', 'desc')
                      ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any client with a confirmed payment
                return $this->getPaymentConfirmedNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for SendCouplesSitePasswordNotification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getSendCouplesSitePasswordNotificationNotifiable()
    {
        // Find clients with upcoming weddings who have access to the couples site
        $client = \App\Models\Client::whereHas('bookings', function($q) {
                $q->whereHas('event', function($q) {
                    $q->where('event_date', '>=', now())
                      ->where('event_type', 'wedding');
                })
                ->where('has_couples_site_access', true);
            })
            ->with(['bookings' => function($q) {
                $q->whereHas('event', function($q) {
                    $q->where('event_date', '>=', now())
                      ->where('event_type', 'wedding');
                })
                ->where('has_couples_site_access', true)
                ->orderBy('created_at', 'desc')
                ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with a wedding booking
            $client = \App\Models\Client::whereHas('bookings', function($q) {
                    $q->whereHas('event', function($q) {
                        $q->where('event_date', '>=', now())
                          ->where('event_type', 'wedding');
                    });
                })
                ->with(['bookings' => function($q) {
                    $q->whereHas('event', function($q) {
                        $q->where('event_date', '>=', now())
                          ->where('event_type', 'wedding');
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any client with a booking
                return $this->getBookingSubmittedNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for SendGroupPasswordNotification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getSendGroupPasswordNotificationNotifiable()
    {
        // Find clients who are part of a group with password protection
        $client = \App\Models\Client::whereHas('groups', function($q) {
                $q->where('is_password_protected', true)
                  ->where('event_date', '>=', now()); // Upcoming groups
            })
            ->with(['groups' => function($q) {
                $q->where('is_password_protected', true)
                  ->where('event_date', '>=', now())
                  ->orderBy('event_date', 'asc')
                  ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client in a group with password protection
            $client = \App\Models\Client::whereHas('groups', function($q) {
                    $q->where('is_password_protected', true);
                })
                ->with(['groups' => function($q) {
                    $q->where('is_password_protected', true)
                      ->orderBy('event_date', 'desc')
                      ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any client in a group
                return $this->getGroupPasswordNotificationNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for TravelDocumentsMail notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getTravelDocumentsMailNotifiable()
    {
        // Find clients with upcoming travel dates who have documents
        $client = \App\Models\Client::whereHas('bookings', function($q) {
                $q->whereHas('documents')
                  ->where('travel_date', '>=', now())
                  ->where('travel_date', '<=', now()->addDays(30)); // Traveling in next 30 days
            })
            ->with(['bookings' => function($q) {
                $q->where('travel_date', '>=', now())
                  ->where('travel_date', '<=', now()->addDays(30))
                  ->whereHas('documents')
                  ->orderBy('travel_date', 'asc')
                  ->with('documents')
                  ->limit(1);
            }])
            ->inRandomOrder()
            ->first();

        if (!$client) {
            // Fallback to any client with documents
            $client = \App\Models\Client::whereHas('bookings.documents')
                ->with(['bookings' => function($q) {
                    $q->whereHas('documents')
                      ->with('documents')
                      ->orderBy('created_at', 'desc')
                      ->limit(1);
                }])
                ->inRandomOrder()
                ->first();

            if (!$client) {
                // Final fallback to any client with a booking
                return $this->getBookingSubmittedNotifiable();
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get notifiable entity for UserCreated notification
     * 
     * @return \App\Models\Client
     * @throws \Exception
     */
    protected function getUserCreatedNotifiable()
    {
        // Find recently created users (last 24 hours)
        $client = \App\Models\Client::where('created_at', '>=', now()->subDay())
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$client) {
            // Fallback to any active user created in the last 7 days
            $client = \App\Models\Client::where('created_at', '>=', now()->subWeek())
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$client) {
                // Final fallback to any active user
                $client = \App\Models\Client::where('is_active', true)
                    ->inRandomOrder()
                    ->first();

                if (!$client) {
                    throw new \Exception('No suitable user found for user created notification');
                }
            }
        }

        return $client;
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for BookingInvoiceFinal notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getBookingInvoiceFinalData()
    {
        // Find a booking client with all required relationships
        $bookingClient = \App\Models\BookingClient::whereHas('booking', function($q) {
                $q->whereHas('payments')
                  ->whereHas('clients');
            })
            ->with([
                'client',
                'booking.payments',
                'booking.clients',
                'booking.group',
                'booking.roomBlocks.room'
            ])
            ->first();

        // The notification expects a single BookingClient instance as its parameter
        return [$bookingClient];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for BookingSubmitted notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getBookingSubmittedData()
    {
        // Find a recently submitted booking
        $booking = \App\Models\Booking::with([
                'client',
                'event',
                'payments' => function($q) {
                    $q->orderBy('created_at', 'desc');
                },
                'invoices' => function($q) {
                    $q->orderBy('created_at', 'desc');
                },
                'bookingItems.service',
                'bookingItems.addons'
            ])
            ->where('status', 'submitted')
            ->where('created_at', '>=', now()->subMonth()) // Recent submissions
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$booking) {
            // Fallback to any booking
            $booking = \App\Models\Booking::with([
                    'client',
                    'event',
                    'payments',
                    'invoices',
                    'bookingItems.service',
                    'bookingItems.addons'
                ])
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$booking) {
                throw new \Exception('No suitable booking found for booking submitted notification');
            }
        }

        $totalAmount = $booking->total_amount ?? 0;
        $depositAmount = $booking->payments->where('type', 'deposit')->sum('amount');
        $balanceDue = $totalAmount - $depositAmount;
        
        return [
            'booking' => $booking,
            'client' => $booking->client,
            'event' => $booking->event ?? (object)['name' => 'Your Event'],
            'booking_date' => $booking->created_at->format('F j, Y'),
            'event_date' => $booking->event_date ? $booking->event_date->format('F j, Y') : 'TBD',
            'total_amount' => number_format($totalAmount, 2),
            'deposit_amount' => number_format($depositAmount, 2),
            'balance_due' => number_format(max(0, $balanceDue), 2),
            'items' => $booking->bookingItems->map(function($item) {
                return [
                    'service' => $item->service->name ?? 'Service',
                    'addons' => $item->addons->pluck('name')->implode(', ') ?: 'None',
                    'quantity' => $item->quantity,
                    'price' => number_format($item->price, 2)
                ];
            }),
            'payments' => $booking->payments->map(function($payment) {
                return [
                    'type' => ucfirst($payment->type),
                    'amount' => number_format($payment->amount, 2),
                    'status' => ucfirst($payment->status),
                    'date' => $payment->created_at->format('M j, Y')
                ];
            }),
            'next_steps' => [
                'Review your booking details',
                'Make any necessary changes',
                'Complete your deposit payment to secure your date'
            ]
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for BookingSubmittedReservationCode notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getBookingSubmittedReservationCodeData()
    {
        // Find a booking with a reservation code and payment
        $booking = \App\Models\Booking::with([
                'client',
                'event',
                'payments' => function($q) {
                    $q->where('status', 'confirmed')
                      ->orderBy('created_at', 'desc');
                },
                'reservationCode'
            ])
            ->whereHas('reservationCode')
            ->where('status', 'confirmed')
            ->where('created_at', '>=', now()->subMonth())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$booking) {
            // Fallback to any booking with a reservation code
            $booking = \App\Models\Booking::with(['client', 'event', 'payments', 'reservationCode'])
                ->whereHas('reservationCode')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$booking) {
                // Final fallback to any confirmed booking
                $booking = \App\Models\Booking::with(['client', 'event', 'payments'])
                    ->where('status', 'confirmed')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$booking) {
                    throw new \Exception('No suitable booking found for booking submitted with reservation code notification');
                }
                
                // Create a mock reservation code
                $booking->setRelation('reservationCode', (object)[
                    'code' => 'RSV-' . strtoupper(uniqid()),
                    'expires_at' => now()->addDays(7),
                    'is_used' => true
                ]);
            }
        }

        $reservationCode = $booking->reservationCode;
        $totalPaid = $booking->payments->sum('amount');
        
        return [
            'booking' => $booking,
            'client' => $booking->client,
            'event' => $booking->event ?? (object)['name' => 'Your Event'],
            'reservation_code' => $reservationCode->code ?? 'RSV-' . strtoupper(uniqid()),
            'expiration_date' => $reservationCode->expires_at ? $reservationCode->expires_at->format('F j, Y \a\t g:i A') : now()->addDays(7)->format('F j, Y \a\t g:i A'),
            'amount_paid' => number_format($totalPaid, 2),
            'booking_date' => $booking->created_at->format('F j, Y'),
            'event_date' => $booking->event_date ? $booking->event_date->format('F j, Y') : 'TBD',
            'next_steps' => [
                'Keep your reservation code safe',
                'Use it for any future communications with our team',
                'Contact us if you need to make any changes to your booking'
            ]
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for BookingSubmittedReservationCodeSeperateInvoice notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getBookingSubmittedReservationCodeSeperateInvoiceData()
    {
        // Find a booking with a reservation code and separate invoice
        $booking = \App\Models\Booking::with([
                'client',
                'event',
                'payments' => function($q) {
                    $q->where('status', 'confirmed')
                      ->orderBy('created_at', 'desc');
                },
                'invoices' => function($q) {
                    $q->where('is_separate', true)
                      ->orderBy('created_at', 'desc');
                },
                'reservationCode'
            ])
            ->whereHas('reservationCode')
            ->whereHas('invoices', function($q) {
                $q->where('is_separate', true);
            })
            ->where('status', 'confirmed')
            ->where('created_at', '>=', now()->subMonth())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$booking) {
            // Fallback to any booking with a reservation code and invoice
            $booking = \App\Models\Booking::with([
                    'client',
                    'event',
                    'payments',
                    'invoices',
                    'reservationCode'
                ])
                ->whereHas('reservationCode')
                ->whereHas('invoices')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$booking) {
                // Final fallback to any confirmed booking with invoices
                $booking = \App\Models\Booking::with(['client', 'event', 'payments', 'invoices'])
                    ->where('status', 'confirmed')
                    ->whereHas('invoices')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$booking) {
                    throw new \Exception('No suitable booking found for booking with separate invoice notification');
                }
                
                // Create a mock reservation code
                $booking->setRelation('reservationCode', (object)[
                    'code' => 'RSV-' . strtoupper(uniqid()),
                    'expires_at' => now()->addDays(7),
                    'is_used' => true
                ]);
                
                // Ensure we have at least one invoice
                if ($booking->invoices->isEmpty()) {
                    $booking->setRelation('invoices', collect([
                        (object)[
                            'id' => 9999,
                            'invoice_number' => 'INV-' . strtoupper(uniqid()),
                            'amount' => 100.00,
                            'due_date' => now()->addDays(14),
                            'is_separate' => true,
                            'items' => [
                                (object)[
                                    'description' => 'Reservation Fee',
                                    'quantity' => 1,
                                    'unit_price' => 100.00,
                                    'total' => 100.00
                                ]
                            ]
                        ]
                    ]));
                }
            }
        }

        $reservationCode = $booking->reservationCode;
        $invoice = $booking->invoices->first();
        $totalPaid = $booking->payments->sum('amount');
        $balanceDue = ($invoice->amount ?? 0) - $totalPaid;
        
        return [
            'booking' => $booking,
            'client' => $booking->client,
            'event' => $booking->event ?? (object)['name' => 'Your Event'],
            'reservation_code' => $reservationCode->code ?? 'RSV-' . strtoupper(uniqid()),
            'invoice_number' => $invoice->invoice_number ?? 'INV-' . strtoupper(uniqid()),
            'invoice_date' => $invoice ? $invoice->created_at->format('F j, Y') : now()->format('F j, Y'),
            'due_date' => $invoice && $invoice->due_date ? $invoice->due_date->format('F j, Y') : now()->addDays(14)->format('F j, Y'),
            'invoice_amount' => number_format($invoice->amount ?? 0, 2),
            'amount_paid' => number_format($totalPaid, 2),
            'balance_due' => number_format(max(0, $balanceDue), 2),
            'invoice_items' => $invoice->items ?? [
                (object)[
                    'description' => 'Reservation Fee',
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'total' => 100.00
                ]
            ],
            'next_steps' => [
                'Review your separate invoice for the reservation fee',
                'Make payment by the due date to secure your booking',
                'Contact us if you have any questions about your invoice'
            ]
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for BrideGroupEmail notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getBrideGroupEmailData()
    {
        // Find a group with bride role and related data
        $group = \App\Models\Group::with([
                'clients' => function($q) {
                    $q->whereHas('roles', function($q) {
                        $q->where('name', 'bride');
                    });
                },
                'bookings' => function($q) {
                    $q->where('status', 'confirmed')
                      ->with(['event', 'bookingItems.service']);
                },
                'reservationCodes'
            ])
            ->whereHas('clients.roles', function($q) {
                $q->where('name', 'bride');
            })
            ->where('event_date', '>=', now())
            ->orderBy('event_date', 'asc')
            ->first();

        if (!$group) {
            // Fallback to any group with a bride
            $group = \App\Models\Group::with(['clients', 'bookings.event', 'reservationCodes'])
                ->whereHas('clients.roles', function($q) {
                    $q->where('name', 'bride');
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$group) {
                throw new \Exception('No suitable group with a bride found for bride group email');
            }
        }

        $bride = $group->clients->first(function($client) {
            return $client->roles->contains('name', 'bride');
        });

        $event = $group->bookings->first()->event ?? null;
        $reservationCode = $group->reservationCodes->first();
        $booking = $group->bookings->first();
        
        return [
            'group' => $group,
            'bride' => $bride,
            'event' => $event ?? (object)['name' => 'Your Special Event'],
            'event_date' => $group->event_date ? $group->event_date->format('F j, Y') : 'TBD',
            'reservation_code' => $reservationCode->code ?? 'RSV-' . strtoupper(uniqid()),
            'booking_reference' => $booking->reference ?? 'BK-' . strtoupper(uniqid()),
            'services' => $booking ? $booking->bookingItems->map(function($item) {
                return [
                    'name' => $item->service->name ?? 'Service',
                    'date' => $item->service_date ? $item->service_date->format('F j, Y \a\t g:i A') : 'To be scheduled',
                    'location' => $item->location ?? 'TBD'
                ];
            }) : [],
            'next_steps' => [
                'Share this information with your bridal party',
                'Contact us if you need to make any changes',
                'Complete any outstanding payments before the due date'
            ]
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for CancellationsLastCalls notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getCancellationsLastCallsData()
    {
        $bookingClient = \App\Models\BookingClient::with(
            ['booking.group'])
            ->whereHas('booking.group', function($query) {
                $query->whereNotNull('cancellation_date');
            })
            ->first();

        return [
            'bookingClient' => $bookingClient,
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for CardDeclined notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getCardDeclinedData()
    {
        // Find a recent failed payment with card details
        $payment = \App\Models\Payment::with(['booking.client', 'booking.event'])
            ->where('status', 'declined')
            ->where('created_at', '>=', now()->subWeek())
            ->whereNotNull('card_last_four')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$payment) {
            // Fallback to any declined payment
            $payment = \App\Models\Payment::with(['booking.client', 'booking.event'])
                ->where('status', 'declined')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$payment) {
                // Final fallback to any payment that can be used to simulate a decline
                $payment = \App\Models\Payment::with(['booking.client', 'booking.event'])
                    ->where('status', '!=', 'refunded')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$payment) {
                    throw new \Exception('No suitable payment found for card declined notification');
                }
                
                // Mock the payment as declined
                $payment->status = 'declined';
                $payment->decline_reason = 'Insufficient funds';
                $payment->card_last_four = '1234';
                $payment->card_brand = 'Visa';
            }
        }

        $booking = $payment->booking;
        $retryDate = now()->addDays(2);
        
        return [
            'payment' => $payment,
            'booking' => $booking,
            'client' => $booking->client ?? (object)['name' => 'Valued Customer', 'email' => 'customer@example.com'],
            'event' => $booking->event ?? (object)['name' => 'Your Booking'],
            'amount' => number_format($payment->amount, 2),
            'card_type' => $payment->card_brand ?? 'credit card',
            'last_four' => $payment->card_last_four ?? '****',
            'decline_reason' => $payment->decline_reason ?? 'Insufficient funds',
            'attempt_date' => $payment->created_at->format('F j, Y \a\t g:i A'),
            'retry_date' => $retryDate->format('F j, Y'),
            'next_steps' => [
                'Update your payment information',
                'Contact your bank if you believe this is an error',
                'Retry payment before ' . $retryDate->format('F j, Y')
            ],
            'support_contact' => [
                'email' => 'support@barefoot.com',
                'phone' => '+1 (800) 123-4567',
                'hours' => 'Monday - Friday, 9 AM - 5 PM EST'
            ]
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for FinalEmail notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getFinalEmailData()
    {
        $guest = \App\Models\Booking::with(['guests'])
            ->whereHas('guests')
            ->first()->guests->first();

        return [
            'guest' => $guest
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for FinalFlightManifestReminder notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getFinalFlightManifestReminderData()
    {
        $bookingClient = \App\Models\BookingClient::with([
            'booking.group',
            'guests',
        ])
        ->whereHas('guests')
        ->first();

        return [
            'bookingClient' => $bookingClient,
            'guestsWithoutManifests' => $bookingClient->guests,
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for FitClientPaymentReminder notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getFitClientPaymentReminderData()
    {
        // Find a FIT booking with an upcoming payment due
        $booking = \App\Models\Booking::with([
                'client',
                'event',
                'payments' => function($q) {
                    $q->where('status', '!=', 'refunded')
                      ->orderBy('due_date', 'asc');
                },
                'bookingItems.service',
                'invoices' => function($q) {
                    $q->where('status', '!=', 'paid')
                      ->orderBy('due_date', 'asc');
                }
            ])
            ->where('booking_type', 'fit') // FIT (Fully Independent Travel) booking
            ->where('status', 'confirmed')
            ->whereHas('invoices', function($q) {
                $q->where('status', '!=', 'paid')
                  ->where('due_date', '<=', now()->addDays(7)) // Due in next 7 days
                  ->where('due_date', '>=', now());
            })
            ->orderByRaw('(SELECT MIN(due_date) FROM invoices WHERE booking_id = bookings.id)')
            ->first();

        if (!$booking) {
            // Fallback to any FIT booking with pending payments
            $booking = \App\Models\Booking::with(['client', 'event', 'payments', 'invoices'])
                ->where('booking_type', 'fit')
                ->where('status', 'confirmed')
                ->whereHas('invoices', function($q) {
                    $q->where('status', '!=', 'paid');
                })
                ->orderBy('departure_date', 'asc')
                ->first();

            if (!$booking) {
                // Final fallback to any FIT booking
                $booking = \App\Models\Booking::with(['client', 'event'])
                    ->where('booking_type', 'fit')
                    ->where('status', 'confirmed')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$booking) {
                    throw new \Exception('No suitable FIT booking found for payment reminder');
                }
                
                // Create mock invoice data
                $dueDate = now()->addDays(5);
                $booking->setRelation('invoices', collect([
                    (object)[
                        'id' => 9999,
                        'invoice_number' => 'INV-' . date('Y') . '-' . rand(1000, 9999),
                        'amount' => 1500.00,
                        'due_date' => $dueDate,
                        'status' => 'pending',
                        'description' => 'Final payment for your FIT package'
                    ]
                ]));
                
                $booking->total_amount = 3000.00;
                $booking->paid_amount = 1500.00;
            }
        }

        $invoice = $booking->invoices->first();
        $totalPaid = $booking->payments->sum('amount');
        $balanceDue = ($booking->total_amount ?? 0) - $totalPaid;
        $daysUntilDue = $invoice ? now()->diffInDays($invoice->due_date, false) : 5;
        $dueDate = $invoice ? $invoice->due_date : now()->addDays(5);
        
        return [
            'booking' => $booking,
            'client' => $booking->client,
            'event' => $booking->event ?? (object)['name' => 'Your Travel Package'],
            'booking_reference' => $booking->reference ?? 'FIT-' . strtoupper(uniqid()),
            'invoice' => [
                'number' => $invoice->invoice_number ?? 'INV-' . date('Y') . '-' . rand(1000, 9999),
                'amount' => number_format($invoice->amount ?? $balanceDue, 2),
                'due_date' => $dueDate->format('l, F j, Y'),
                'status' => $invoice->status ?? 'pending',
                'description' => $invoice->description ?? 'Payment for your FIT travel package'
            ],
            'payment_summary' => [
                'total_amount' => number_format($booking->total_amount ?? 3000.00, 2),
                'paid_amount' => number_format($totalPaid, 2),
                'balance_due' => number_format($balanceDue, 2),
                'currency' => 'USD'
            ],
            'days_until_due' => $daysUntilDue,
            'is_past_due' => $daysUntilDue < 0,
            'payment_methods' => [
                'credit_card' => true,
                'bank_transfer' => true,
                'paypal' => true
            ],
            'next_steps' => [
                'Review the invoice details',
                'Make payment before the due date',
                'Contact us if you have any questions about your invoice',
                'Save your payment confirmation for your records'
            ],
            'support_contact' => [
                'email' => 'payments@barefoot.com',
                'phone' => '+1 (800) 123-4567',
                'hours' => 'Monday - Friday, 9 AM - 5 PM EST'
            ]
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for FitQuoteAccepted notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getFitQuoteAcceptedData()
    {
        // Find a recently accepted FIT quote
        $quote = \App\Models\Quote::with([
                'client',
                'user', // Agent who created the quote
                'booking',
                'quoteItems.service',
                'documents'
            ])
            ->where('status', 'accepted')
            ->where('accepted_at', '>=', now()->subDays(7)) // Accepted in the last 7 days
            ->orderBy('accepted_at', 'desc')
            ->first();

        if (!$quote) {
            // Fallback to any accepted FIT quote
            $quote = \App\Models\Quote::with(['client', 'user', 'booking', 'quoteItems.service', 'documents'])
                ->where('status', 'accepted')
                ->orderBy('accepted_at', 'desc')
                ->first();

            if (!$quote) {
                // Final fallback - create a mock quote
                $quote = (object)[
                    'id' => 9999,
                    'quote_number' => 'QT-' . date('Y') . '-' . strtoupper(uniqid()),
                    'status' => 'accepted',
                    'accepted_at' => now(),
                    'expires_at' => now()->addDays(30),
                    'total_amount' => 3500.00,
                    'deposit_amount' => 875.00,
                    'client' => (object)[
                        'name' => 'John Smith',
                        'email' => 'john.smith@example.com',
                        'phone' => '+1 (555) 123-4567',
                        'address' => '123 Main St, Anytown, USA'
                    ],
                    'user' => (object)[
                        'name' => 'Jane Doe',
                        'email' => 'jane.doe@barefoot.com',
                        'phone' => '+1 (800) 555-0199',
                        'job_title' => 'Travel Specialist'
                    ],
                    'quoteItems' => collect([
                        (object)[
                            'id' => 1,
                            'description' => '7-Night Luxury Resort Stay',
                            'quantity' => 2,
                            'unit_price' => 1000.00,
                            'total_price' => 2000.00,
                            'service' => (object)['name' => 'Luxury Resort Package']
                        ],
                        (object)[
                            'id' => 2,
                            'description' => 'Roundtrip Airport Transfers',
                            'quantity' => 1,
                            'unit_price' => 150.00,
                            'total_price' => 150.00,
                            'service' => (object)['name' => 'Airport Transfer']
                        ]
                    ]),
                    'documents' => collect([])
                ];
                
                // Add mock documents
                $quote->documents = collect([
                    (object)[
                        'id' => 1,
                        'name' => 'Detailed Itinerary',
                        'file_path' => '/documents/itinerary.pdf',
                        'type' => 'itinerary',
                        'created_at' => now()
                    ],
                    (object)[
                        'id' => 2,
                        'name' => 'Terms and Conditions',
                        'file_path' => '/documents/terms.pdf',
                        'type' => 'terms',
                        'created_at' => now()
                    ]
                ]);
            }
        }

        $depositDueDate = $quote->accepted_at ? $quote->accepted_at->addDays(7) : now()->addDays(7);
        $finalPaymentDate = $depositDueDate->copy()->addDays(21); // 30 days from acceptance
        
        return [
            'quote' => $quote,
            'client' => $quote->client,
            'agent' => $quote->user ?? (object)['name' => 'Your Travel Specialist', 'email' => 'travel@barefoot.com'],
            'quote_number' => $quote->quote_number ?? 'QT-' . date('Y') . '-' . strtoupper(uniqid()),
            'status' => 'accepted',
            'accepted_date' => $quote->accepted_at ? $quote->accepted_at->format('l, F j, Y') : now()->format('l, F j, Y'),
            'expiration_date' => $quote->expires_at ? $quote->expires_at->format('l, F j, Y') : now()->addDays(30)->format('l, F j, Y'),
            'total_amount' => number_format($quote->total_amount ?? 0, 2),
            'deposit_amount' => number_format($quote->deposit_amount ?? 0, 2),
            'balance_due' => number_format(($quote->total_amount ?? 0) - ($quote->deposit_amount ?? 0), 2),
            'deposit_due_date' => $depositDueDate->format('l, F j, Y'),
            'final_payment_date' => $finalPaymentDate->format('l, F j, Y'),
            'itinerary' => $quote->quoteItems->map(function($item) {
                return [
                    'description' => $item->description,
                    'service' => $item->service->name ?? 'Service',
                    'quantity' => $item->quantity,
                    'unit_price' => number_format($item->unit_price ?? 0, 2),
                    'total_price' => number_format($item->total_price ?? 0, 2)
                ];
            }),
            'documents' => $quote->documents->map(function($doc) {
                return [
                    'name' => $doc->name,
                    'url' => $doc->file_path ? url($doc->file_path) : '#',
                    'type' => $doc->type,
                    'uploaded_date' => $doc->created_at ? $doc->created_at->format('M j, Y') : 'N/A'
                ];
            }),
            'next_steps' => [
                'Review your quote details carefully',
                'Make the required deposit by ' . $depositDueDate->format('F j, Y'),
                'Contact your travel specialist with any questions',
                'Review all travel documents and requirements'
            ],
            'support_contact' => [
                'email' => 'quotes@barefoot.com',
                'phone' => '+1 (800) 123-4567',
                'hours' => 'Monday - Friday, 9 AM - 5 PM EST'
            ]
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for FitQuoteCancelled notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getFitQuoteCancelledData()
    {
        // Find a recently cancelled FIT quote
        $quote = \App\Models\Quote::with([
                'client',
                'user', // Agent who handled the quote
                'cancelledBy', // User who cancelled the quote
                'quoteItems.service'
            ])
            ->where('status', 'cancelled')
            ->where('cancelled_at', '>=', now()->subDays(7)) // Cancelled in the last 7 days
            ->orderBy('cancelled_at', 'desc')
            ->first();

        if (!$quote) {
            // Fallback to any cancelled FIT quote
            $quote = \App\Models\Quote::with(['client', 'user', 'cancelledBy', 'quoteItems.service'])
                ->where('status', 'cancelled')
                ->orderBy('cancelled_at', 'desc')
                ->first();

            if (!$quote) {
                // Final fallback - create a mock quote
                $cancelledAt = now()->subDays(2);
                $quote = (object)[
                    'id' => 9999,
                    'quote_number' => 'QT-' . date('Y') . '-' . strtoupper(uniqid()),
                    'status' => 'cancelled',
                    'created_at' => now()->subDays(10),
                    'cancelled_at' => $cancelledAt,
                    'cancellation_reason' => 'Client decided not to proceed',
                    'cancellation_notes' => 'Client found alternative arrangements',
                    'total_amount' => 2800.00,
                    'client' => (object)[
                        'name' => 'Robert Johnson',
                        'email' => 'robert.j@example.com',
                        'phone' => '+1 (555) 987-6543'
                    ],
                    'user' => (object)[
                        'name' => 'Sarah Wilson',
                        'email' => 'sarah.w@barefoot.com',
                        'job_title' => 'Travel Consultant'
                    ],
                    'cancelledBy' => (object)[
                        'name' => 'Robert Johnson',
                        'email' => 'robert.j@example.com'
                    ],
                    'quoteItems' => collect([
                        (object)[
                            'description' => '5-Night Beach Resort Package',
                            'quantity' => 2,
                            'unit_price' => 1200.00,
                            'total_price' => 2400.00,
                            'service' => (object)['name' => 'Beach Resort Package']
                        ],
                        (object)[
                            'description' => 'Adventure Excursion',
                            'quantity' => 1,
                            'unit_price' => 400.00,
                            'total_price' => 400.00,
                            'service' => (object)['name' => 'Adventure Tour']
                        ]
                    ])
                ];
            }
        }

        $cancellationDate = $quote->cancelled_at ?? now()->subDays(1);
        $createdDate = $quote->created_at ?? now()->subDays(10);
        $daysActive = $createdDate->diffInDays($cancellationDate);
        
        return [
            'quote' => $quote,
            'client' => $quote->client,
            'agent' => $quote->user ?? (object)['name' => 'Travel Specialist', 'email' => 'travel@barefoot.com'],
            'cancelled_by' => $quote->cancelledBy ?? $quote->client,
            'quote_number' => $quote->quote_number ?? 'QT-' . date('Y') . '-' . strtoupper(uniqid()),
            'status' => 'cancelled',
            'created_date' => $createdDate->format('l, F j, Y'),
            'cancellation_date' => $cancellationDate->format('l, F j, Y'),
            'days_active' => $daysActive,
            'cancellation_reason' => $quote->cancellation_reason ?? 'Not specified',
            'cancellation_notes' => $quote->cancellation_notes ?? 'No additional notes provided',
            'total_amount' => number_format($quote->total_amount ?? 0, 2),
            'itinerary_summary' => $quote->quoteItems->map(function($item) {
                return [
                    'description' => $item->description,
                    'service' => $item->service->name ?? 'Service',
                    'quantity' => $item->quantity,
                    'total' => number_format($item->total_price ?? 0, 2)
                ];
            }),
            'refund_information' => [
                'eligibility' => $daysActive <= 7 ? 'Full refund eligible' : 'Partial refund may apply',
                'amount' => $daysActive <= 7 ? number_format($quote->total_amount ?? 0, 2) : '0.00',
                'process_time' => '7-10 business days',
                'method' => 'Original payment method'
            ],
            'next_steps' => [
                'Review the cancellation details',
                'Contact us if you believe this cancellation is in error',
                'Consider alternative travel dates or destinations',
                'Check your email for any refund confirmation'
            ],
            'support_contact' => [
                'email' => 'support@barefoot.com',
                'phone' => '+1 (800) 123-4567',
                'hours' => 'Monday - Friday, 9 AM - 5 PM EST'
            ]
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for FitQuoteMail notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getFitQuoteMailData()
    {
        // Find a recently created FIT quote
        $quote = \App\Models\Quote::with([
                'client',
                'user', // Agent who created the quote
                'quoteItems.service',
                'documents'
            ])
            ->where('status', 'draft')
            ->where('created_at', '>=', now()->subDays(3)) // Created in the last 3 days
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$quote) {
            // Fallback to any draft FIT quote
            $quote = \App\Models\Quote::with(['client', 'user', 'quoteItems.service', 'documents'])
                ->where('status', 'draft')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$quote) {
                // Final fallback - create a mock quote
                $quote = (object)[
                    'id' => 9999,
                    'quote_number' => 'QT-' . date('Y') . '-' . strtoupper(uniqid()),
                    'status' => 'draft',
                    'created_at' => now(),
                    'expires_at' => now()->addDays(14),
                    'total_amount' => 4250.00,
                    'deposit_amount' => 1062.50,
                    'client' => (object)[
                        'name' => 'Michael Brown',
                        'email' => 'michael.b@example.com',
                        'phone' => '+1 (555) 123-7890',
                        'address' => '456 Oak Avenue, Somewhere, ST 12345'
                    ],
                    'user' => (object)[
                        'name' => 'Emily Chen',
                        'email' => 'emily.c@barefoot.com',
                        'phone' => '+1 (800) 555-1234',
                        'job_title' => 'Travel Designer'
                    ],
                    'quoteItems' => collect([
                        (object)[
                            'id' => 1,
                            'description' => '8-Night Luxury Safari Package',
                            'quantity' => 2,
                            'unit_price' => 1800.00,
                            'total_price' => 3600.00,
                            'service' => (object)['name' => 'Luxury Safari Package'],
                            'notes' => 'Includes all meals and guided tours'
                        ],
                        (object)[
                            'id' => 2,
                            'description' => 'Hot Air Balloon Ride',
                            'quantity' => 2,
                            'unit_price' => 250.00,
                            'total_price' => 500.00,
                            'service' => (object)['name' => 'Balloon Safari'],
                            'notes' => 'Sunrise experience with champagne breakfast'
                        ],
                        (object)[
                            'id' => 3,
                            'description' => 'Airport VIP Meet & Greet',
                            'quantity' => 1,
                            'unit_price' => 150.00,
                            'total_price' => 150.00,
                            'service' => (object)['name' => 'VIP Services'],
                            'notes' => 'Expedited immigration and baggage handling'
                        ]
                    ]),
                    'documents' => collect([])
                ];
                
                // Add mock documents
                $quote->documents = collect([
                    (object)[
                        'id' => 1,
                        'name' => 'Safari Itinerary',
                        'file_path' => '/documents/safari-itinerary.pdf',
                        'type' => 'itinerary',
                        'created_at' => now()
                    ],
                    (object)[
                        'id' => 2,
                        'name' => 'Packing List',
                        'file_path' => '/documents/safari-packing-list.pdf',
                        'type' => 'packing',
                        'created_at' => now()
                    ]
                ]);
            }
        }

        $expirationDate = $quote->expires_at ?? now()->addDays(14);
        $depositDueDate = now()->addDays(7);
        
        return [
            'quote' => $quote,
            'client' => $quote->client,
            'agent' => $quote->user ?? (object)['name' => 'Your Travel Specialist', 'email' => 'travel@barefoot.com'],
            'quote_number' => $quote->quote_number ?? 'QT-' . date('Y') . '-' . strtoupper(uniqid()),
            'status' => $quote->status ?? 'draft',
            'created_date' => $quote->created_at ? $quote->created_at->format('l, F j, Y') : now()->format('l, F j, Y'),
            'expiration_date' => $expirationDate->format('l, F j, Y'),
            'days_valid' => now()->diffInDays($expirationDate),
            'total_amount' => number_format($quote->total_amount ?? 0, 2),
            'deposit_amount' => number_format($quote->deposit_amount ?? 0, 2),
            'deposit_due_date' => $depositDueDate->format('l, F j, Y'),
            'itinerary' => $quote->quoteItems->map(function($item) {
                return [
                    'description' => $item->description,
                    'service' => $item->service->name ?? 'Service',
                    'quantity' => $item->quantity,
                    'unit_price' => number_format($item->unit_price ?? 0, 2),
                    'total_price' => number_format($item->total_price ?? 0, 2),
                    'notes' => $item->notes ?? ''
                ];
            }),
            'documents' => $quote->documents->map(function($doc) {
                return [
                    'name' => $doc->name,
                    'url' => $doc->file_path ? url($doc->file_path) : '#',
                    'type' => $doc->type,
                    'uploaded_date' => $doc->created_at ? $doc->created_at->format('M j, Y') : 'N/A'
                ];
            }),
            'next_steps' => [
                'Review your personalized quote',
                'Contact your travel specialist with any questions',
                'Secure your booking with a deposit by ' . $depositDueDate->format('F j, Y'),
                'Review the terms and conditions',
                'Check your email for additional documents'
            ],
            'support_contact' => [
                'email' => 'quotes@barefoot.com',
                'phone' => '+1 (800) 123-4567',
                'hours' => 'Monday - Friday, 9 AM - 5 PM EST'
            ]
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for FitQuoteReminder notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getFitQuoteReminderData()
    {
        $fitQuote = \App\Models\FitQuote::with([
                'bookingClient.booking.group'
            ])
            ->first();

        return [
            'fitQuote' => $fitQuote
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for FlightManifestRequest notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getFlightManifestRequestData()
    {
        $bookingClient = \App\Models\BookingClient::with([
            'booking.group',
            'guests',
        ])
        ->whereHas('guests')
        ->whereHas('booking.group', function($q) {
            $q->whereNotNull('transportation_submit_before');
        })
        ->first();

        return [
            'bookingClient' => $bookingClient,
            'guestsWithoutManifests' => $bookingClient->guests,
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for FlightManifestSubmitted notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getFlightManifestSubmittedData()
    {
        // Find a booking with a recently submitted manifest
        $booking = \App\Models\Booking::with([
                'client',
                'user', // Agent
                'flights' => function($query) {
                    $query->whereNotNull('manifest_submitted_at')
                          ->where('departure_date', '>=', now())
                          ->orderBy('departure_date', 'asc');
                },
                'passengers',
                'documents'
            ])
            ->whereHas('flights', function($query) {
                $query->whereNotNull('manifest_submitted_at')
                      ->where('manifest_submitted_at', '>=', now()->subDays(1)); // Submitted in last 24 hours
            })
            ->whereIn('status', ['confirmed', 'partially_paid'])
            ->orderByRaw(
                "(SELECT MAX(manifest_submitted_at) FROM flights 
                  WHERE booking_id = bookings.id 
                  AND manifest_submitted_at IS NOT NULL)"
            )
            ->first();

        if (!$booking) {
            // Fallback to any booking with submitted manifest
            $booking = \App\Models\Booking::with([
                    'client',
                    'user',
                    'flights' => function($query) {
                        $query->whereNotNull('manifest_submitted_at')
                              ->where('departure_date', '>=', now())
                              ->orderBy('departure_date', 'asc');
                    },
                    'passengers',
                    'documents'
                ])
                ->whereHas('flights', function($query) {
                    $query->whereNotNull('manifest_submitted_at');
                })
                ->whereIn('status', ['confirmed', 'partially_paid'])
                ->orderByRaw(
                    "(SELECT MAX(manifest_submitted_at) FROM flights 
                      WHERE booking_id = bookings.id 
                      AND manifest_submitted_at IS NOT NULL)"
                )
                ->first();

            if (!$booking) {
                // Final fallback - create a mock booking with submitted manifest
                $departureDate = now()->addDays(5);
                $submittedAt = now()->subHours(2);
                $booking = (object)[
                    'id' => 9999,
                    'booking_number' => 'BK-' . date('Y') . '-' . strtoupper(Str::random(8)),
                    'status' => 'confirmed',
                    'created_at' => now()->subDays(10),
                    'client' => (object)[
                        'name' => 'Michael Chen',
                        'email' => 'michael.c@example.com',
                        'phone' => '+1 (555) 369-2580',
                        'address' => '159 Cherry Lane, Anywhere, ST 54321'
                    ],
                    'user' => (object)[
                        'name' => 'Jessica Taylor',
                        'email' => 'jessica.t@barefoot.com',
                        'phone' => '+1 (800) 555-3690',
                        'job_title' => 'Flight Operations Manager'
                    ],
                    'flights' => collect([
                        (object)[
                            'id' => 1,
                            'airline' => 'Oceanic Airlines',
                            'flight_number' => 'OA 789',
                            'departure_airport' => 'SFO',
                            'arrival_airport' => 'HNL',
                            'departure_date' => $departureDate->copy()->setTime(9, 15),
                            'arrival_date' => $departureDate->copy()->setTime(12, 45),
                            'booking_reference' => 'OA' . strtoupper(Str::random(6)),
                            'manifest_deadline' => $departureDate->copy()->subDays(3),
                            'manifest_submitted_at' => $submittedAt,
                            'is_international' => false,
                            'gate' => 'A15',
                            'terminal' => '1',
                            'seat_assignment' => '24A, 24B',
                            'confirmation_number' => 'OCEAN' . rand(1000, 9999)
                        ]
                    ]),
                    'passengers' => collect([
                        (object)[
                            'id' => 1,
                            'first_name' => 'Michael',
                            'last_name' => 'Chen',
                            'date_of_birth' => now()->subYears(40)->format('Y-m-d'),
                            'passport_number' => 'P' . rand(1000000, 9999999),
                            'passport_expiry' => now()->addYears(4, 6)->format('Y-m-d'),
                            'nationality' => 'US',
                            'is_primary' => true,
                            'seat_assignment' => '24A'
                        ],
                        (object)[
                            'id' => 2,
                            'first_name' => 'Sarah',
                            'last_name' => 'Chen',
                            'date_of_birth' => now()->subYears(38)->format('Y-m-d'),
                            'passport_number' => 'P' . rand(1000000, 9999999),
                            'passport_expiry' => now()->addYears(5, 3)->format('Y-m-d'),
                            'nationality' => 'US',
                            'is_primary' => false,
                            'seat_assignment' => '24B'
                        ]
                    ]),
                    'documents' => collect([
                        (object)[
                            'id' => 1,
                            'name' => 'E-Ticket Receipt',
                            'file_path' => '/documents/eticket-receipt.pdf',
                            'type' => 'ticket',
                            'created_at' => now()->subHours(3)
                        ],
                        (object)[
                            'id' => 2,
                            'name' => 'Boarding Pass',
                            'file_path' => '/documents/boarding-pass.pdf',
                            'type' => 'boarding_pass',
                            'created_at' => now()->subHours(1)
                        ]
                    ])
                ];
            }
        }

        // Get the most recently submitted flight
        $submittedFlight = $booking->flights->sortByDesc('manifest_submitted_at')->first();
        $departureDate = $submittedFlight->departure_date ?? now()->addDays(5);
        $submittedAt = $submittedFlight->manifest_submitted_at ?? now()->subHours(2);
        $isInternational = $submittedFlight ? (bool)$submittedFlight->is_international : false;
        $daysUntilFlight = now()->diffInDays($departureDate, false);
        
        return [
            'booking' => $booking,
            'booking_number' => $booking->booking_number ?? 'BK-' . date('Y') . '-' . strtoupper(Str::random(8)),
            'status' => $booking->status ?? 'confirmed',
            'client' => $booking->client,
            'agent' => $booking->user ?? (object)['name' => 'Flight Operations', 'email' => 'flights@barefoot.com'],
            'flight' => $submittedFlight ? [
                'id' => $submittedFlight->id,
                'airline' => $submittedFlight->airline,
                'flight_number' => $submittedFlight->flight_number,
                'departure_airport' => $submittedFlight->departure_airport,
                'arrival_airport' => $submittedFlight->arrival_airport,
                'departure_date' => $departureDate->format('l, F j, Y'),
                'departure_time' => $departureDate->format('g:i A'),
                'arrival_date' => $submittedFlight->arrival_date ? $submittedFlight->arrival_date->format('l, F j, Y') : 'N/A',
                'arrival_time' => $submittedFlight->arrival_date ? $submittedFlight->arrival_date->format('g:i A') : 'N/A',
                'booking_reference' => $submittedFlight->booking_reference,
                'confirmation_number' => $submittedFlight->confirmation_number ?? 'N/A',
                'gate' => $submittedFlight->gate ?? 'To be announced',
                'terminal' => $submittedFlight->terminal ?? 'To be announced',
                'is_international' => $isInternational,
                'seat_assignment' => $submittedFlight->seat_assignment ?? 'To be assigned',
                'manifest_submitted_at' => $submittedAt->format('l, F j, Y g:i A'),
                'days_until_flight' => $daysUntilFlight
            ] : null,
            'passengers' => $booking->passengers->map(function($passenger) {
                return [
                    'id' => $passenger->id,
                    'name' => "{$passenger->first_name} {$passenger->last_name}",
                    'first_name' => $passenger->first_name,
                    'last_name' => $passenger->last_name,
                    'date_of_birth' => $passenger->date_of_birth ? \Carbon\Carbon::parse($passenger->date_of_birth)->format('F j, Y') : 'N/A',
                    'passport_number' => $passenger->passport_number,
                    'passport_expiry' => $passenger->passport_expiry ? \Carbon\Carbon::parse($passenger->passport_expiry)->format('F j, Y') : 'N/A',
                    'nationality' => $passenger->nationality,
                    'is_primary' => (bool)$passenger->is_primary,
                    'age' => $passenger->date_of_birth ? \Carbon\Carbon::parse($passenger->date_of_birth)->age : 'N/A',
                    'seat_assignment' => $passenger->seat_assignment ?? 'To be assigned'
                ];
            }),
            'documents' => $booking->documents->map(function($doc) {
                return [
                    'name' => $doc->name,
                    'url' => $doc->file_path ? url($doc->file_path) : '#',
                    'type' => $doc->type,
                    'uploaded_date' => $doc->created_at ? $doc->created_at->format('M j, Y g:i A') : 'N/A'
                ];
            }),
            'submission_details' => [
                'submitted_at' => $submittedAt->format('l, F j, Y g:i A'),
                'submitted_by' => $booking->user ? $booking->user->name : 'System',
                'reference_number' => 'MNF-' . strtoupper(Str::random(8)),
                'status' => 'Confirmed',
                'next_steps' => array_merge(
                    [
                        'Review your flight details below',
                        'Check-in online 24 hours before departure',
                        'Have your travel documents ready for security',
                        'Arrive at the airport at least 2 hours before departure',
                        'Keep this confirmation for your records'
                    ],
                    $isInternational ? [
                        'Ensure you have all required travel documents for international travel',
                        'Verify visa requirements for your destination'
                    ] : []
                )
            ],
            'travel_tips' => [
                'Check baggage allowance and restrictions',
                'Download the airline app for real-time updates',
                $isInternational ? 'Complete any required arrival forms before landing' : 'Review TSA guidelines for carry-on items',
                'Verify terminal and gate information before heading to the airport'
            ],
            'support_contact' => [
                'email' => 'flightsupport@barefoot.com',
                'phone' => '+1 (800) 555-7890',
                'emergency_phone' => '+1 (800) 555-1122',
                'hours' => '24/7 for flight-related emergencies',
                'contact_person' => 'Flight Support Team'
            ]
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for GroupEmail notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getGroupEmailData()
    {
        // Get a group that has been recently active or created
        $group = \App\Models\Group::with([
                'organizer',
                'members' => function($query) {
                    $query->where('is_active', true)
                          ->orderBy('is_organizer', 'desc')
                          ->orderBy('name');
                },
                'destination',
                'payments',
                'documents'
            ])
            ->where('departure_date', '>=', now())
            ->where(function($query) {
                $query->where('created_at', '>=', now()->subDays(30))
                      ->orWhereHas('payments', function($q) {
                          $q->where('created_at', '>=', now()->subDays(14));
                      });
            })
            ->orderBy('departure_date', 'asc')
            ->first();

        if (!$group) {
            // Fallback to any active group
            $group = \App\Models\Group::with([
                    'organizer',
                    'members' => function($query) {
                        $query->where('is_active', true)
                              ->orderBy('is_organizer', 'desc')
                              ->orderBy('name');
                    },
                    'destination',
                    'payments',
                    'documents'
                ])
                ->where('departure_date', '>=', now())
                ->orderBy('departure_date', 'asc')
                ->first();

            if (!$group) {
                // Final fallback - create a mock group
                $departureDate = now()->addMonths(3);
                $group = (object)[
                    'id' => 9999,
                    'name' => 'Summer Adventure Group 2023',
                    'code' => 'GRP' . strtoupper(Str::random(6)),
                    'status' => 'open',
                    'description' => 'Annual summer adventure trip to exotic locations',
                    'departure_date' => $departureDate,
                    'return_date' => $departureDate->copy()->addDays(10),
                    'max_participants' => 20,
                    'current_participants' => 15,
                    'price_per_person' => 2499.99,
                    'deposit_amount' => 500.00,
                    'balance_due_date' => $departureDate->copy()->subDays(60),
                    'created_at' => now()->subDays(45),
                    'organizer' => (object)[
                        'id' => 1001,
                        'name' => 'Alex Johnson',
                        'email' => 'alex.j@example.com',
                        'phone' => '+1 (555) 123-4567',
                        'avatar' => '/img/avatars/org1.jpg'
                    ],
                    'destination' => (object)[
                        'id' => 1,
                        'name' => 'Bali, Indonesia',
                        'code' => 'DPS',
                        'description' => 'Beautiful beaches and cultural experiences',
                        'image' => '/img/destinations/bali.jpg'
                    ],
                    'members' => collect([
                        (object)[
                            'id' => 1001,
                            'name' => 'Alex Johnson',
                            'email' => 'alex.j@example.com',
                            'phone' => '+1 (555) 123-4567',
                            'is_organizer' => true,
                            'joined_at' => now()->subDays(45),
                            'status' => 'confirmed',
                            'room_preference' => 'double',
                            'dietary_restrictions' => 'None',
                            'emergency_contact' => 'Sarah Johnson (Spouse) +1 (555) 987-6543'
                        ],
                        (object)[
                            'id' => 1002,
                            'name' => 'Taylor Smith',
                            'email' => 'taylor.s@example.com',
                            'phone' => '+1 (555) 234-5678',
                            'is_organizer' => false,
                            'joined_at' => now()->subDays(40),
                            'status' => 'confirmed',
                            'room_preference' => 'shared',
                            'dietary_restrictions' => 'Vegetarian',
                            'emergency_contact' => 'Jordan Smith (Sibling) +1 (555) 876-5432'
                        ],
                        (object)[
                            'id' => 1003,
                            'name' => 'Jordan Lee',
                            'email' => 'jordan.l@example.com',
                            'phone' => '+1 (555) 345-6789',
                            'is_organizer' => false,
                            'joined_at' => now()->subDays(35),
                            'status' => 'pending_payment',
                            'room_preference' => 'single',
                            'dietary_restrictions' => 'Gluten-free',
                            'emergency_contact' => 'Casey Lee (Partner) +1 (555) 765-4321'
                        ]
                    ]),
                    'payments' => collect([
                        (object)[
                            'id' => 5001,
                            'amount' => 500.00,
                            'payment_date' => now()->subDays(40),
                            'status' => 'completed',
                            'payment_method' => 'credit_card',
                            'transaction_id' => 'TXN' . strtoupper(Str::random(10)),
                            'notes' => 'Initial deposit'
                        ],
                        (object)[
                            'id' => 5002,
                            'amount' => 1000.00,
                            'payment_date' => now()->subDays(10),
                            'status' => 'completed',
                            'payment_method' => 'bank_transfer',
                            'transaction_id' => 'TXN' . strtoupper(Str::random(10)),
                            'notes' => 'First installment'
                        ]
                    ]),
                    'documents' => collect([
                        (object)[
                            'id' => 3001,
                            'name' => 'Itinerary - Bali Adventure',
                            'file_path' => '/documents/itinerary-bali-2023.pdf',
                            'type' => 'itinerary',
                            'uploaded_at' => now()->subDays(30)
                        ],
                        (object)[
                            'id' => 3002,
                            'name' => 'Packing List',
                            'file_path' => '/documents/packing-list-tropical.pdf',
                            'type' => 'information',
                            'uploaded_at' => now()->subDays(25)
                        ],
                        (object)[
                            'id' => 3003,
                            'name' => 'Visa Requirements',
                            'file_path' => '/documents/indonesia-visa-requirements.pdf',
                            'type' => 'visa',
                            'uploaded_at' => now()->subDays(20)
                        ]
                    ])
                ];
            }
        }

        $daysUntilDeparture = $group->departure_date ? now()->diffInDays($group->departure_date, false) : 0;
        $isUpcoming = $daysUntilDeparture > 0;
        $paymentStatus = $group->payments->sum('amount') >= $group->price_per_person ? 'paid_in_full' : 'balance_due';
        $balanceDue = max(0, $group->price_per_person - $group->payments->sum('amount'));
        
        return [
            'group' => $group,
            'group_name' => $group->name,
            'group_code' => $group->code,
            'status' => $group->status,
            'description' => $group->description,
            'organizer' => $group->organizer ?? (object)['name' => 'Group Organizer', 'email' => 'organizer@example.com'],
            'destination' => $group->destination ?? (object)['name' => 'Destination TBD', 'description' => 'Details coming soon'],
            'trip_dates' => [
                'departure_date' => $group->departure_date ? $group->departure_date->format('l, F j, Y') : 'TBD',
                'return_date' => $group->return_date ? $group->return_date->format('l, F j, Y') : 'TBD',
                'duration_nights' => $group->departure_date && $group->return_date 
                    ? $group->departure_date->diffInDays($group->return_date) 
                    : 0,
                'days_until_departure' => $daysUntilDeparture,
                'is_upcoming' => $isUpcoming
            ],
            'pricing' => [
                'price_per_person' => number_format($group->price_per_person ?? 0, 2),
                'deposit_amount' => number_format($group->deposit_amount ?? 0, 2),
                'balance_due' => number_format($balanceDue, 2),
                'balance_due_date' => $group->balance_due_date ? $group->balance_due_date->format('l, F j, Y') : 'TBD',
                'payment_status' => $paymentStatus,
                'payment_plan' => [
                    'deposit' => number_format($group->deposit_amount ?? 0, 2) . ' due upon registration',
                    'first_payment' => '50% of balance due by ' . ($group->departure_date ? $group->departure_date->copy()->subMonths(3)->format('F j, Y') : 'TBD'),
                    'final_payment' => 'Remaining balance due by ' . ($group->balance_due_date ? $group->balance_due_date->format('F j, Y') : 'TBD')
                ]
            ],
            'participants' => [
                'total' => count($group->members ?? []),
                'max' => $group->max_participants ?? 0,
                'spots_remaining' => ($group->max_participants ?? 0) - count($group->members ?? []),
                'list' => $group->members->map(function($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'phone' => $member->phone,
                        'is_organizer' => (bool)$member->is_organizer,
                        'status' => $member->status ?? 'confirmed',
                        'joined_date' => $member->joined_at ? $member->joined_at->format('M j, Y') : 'N/A',
                        'room_preference' => $member->room_preference ?? 'Not specified',
                        'dietary_restrictions' => $member->dietary_restrictions ?? 'None',
                        'emergency_contact' => $member->emergency_contact ?? 'Not provided'
                    ];
                })
            ],
            'documents' => $group->documents->map(function($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'url' => $doc->file_path ? url($doc->file_path) : '#',
                    'type' => $doc->type,
                    'uploaded_date' => $doc->uploaded_at ? $doc->uploaded_at->format('M j, Y') : 'N/A',
                    'icon' => $this->getDocumentIcon($doc->type)
                ];
            }),
            'upcoming_events' => [
                [
                    'title' => 'Final Payment Due',
                    'date' => $group->balance_due_date ? $group->balance_due_date->format('F j, Y') : 'TBD',
                    'description' => 'Last day to submit final payment',
                    'is_important' => true
                ],
                [
                    'title' => 'Pre-Trip Meeting',
                    'date' => $group->departure_date ? $group->departure_date->copy()->subWeeks(2)->format('F j, Y') : 'TBD',
                    'description' => 'Virtual meeting to discuss final details',
                    'is_important' => true
                ],
                [
                    'title' => 'Visa Application Deadline',
                    'date' => $group->departure_date ? $group->departure_date->copy()->subMonths(2)->format('F j, Y') : 'TBD',
                    'description' => 'Last day to apply for required visas',
                    'is_important' => true
                ]
            ],
            'next_steps' => array_merge(
                [
                    'Review the trip itinerary and documents',
                    'Submit any outstanding payments',
                    'Update your profile with emergency contact information',
                    'Check visa requirements for your nationality',
                    'Review packing list and travel tips'
                ],
                $paymentStatus === 'balance_due' ? [
                    'Your balance of $' . number_format($balanceDue, 2) . ' is due by ' . ($group->balance_due_date ? $group->balance_due_date->format('F j, Y') : 'the deadline'),
                    'Log in to your account to make a payment or set up a payment plan'
                ] : []
            ),
            'support_contact' => [
                'email' => 'groups@barefoot.com',
                'phone' => '+1 (800) 555-1234',
                'hours' => 'Monday - Friday, 9 AM - 6 PM EST',
                'emergency_phone' => '+1 (800) 555-5678',
                'contact_person' => 'Group Travel Department'
            ]
        ];
    }
    
    /**
     * Get icon class for document type
     * 
     * @param string $type
     * @return string
     */
    protected function getDocumentIcon($type)
    {
        $icons = [
            'itinerary' => 'fa-route',
            'visa' => 'fa-passport',
            'information' => 'fa-info-circle',
            'form' => 'fa-file-alt',
            'ticket' => 'fa-ticket-alt',
            'receipt' => 'fa-file-invoice-dollar',
            'insurance' => 'fa-shield-alt',
            'default' => 'fa-file'
        ];
        
        return $icons[strtolower($type)] ?? $icons['default'];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for GroupPasswordNotification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getGroupPasswordNotificationData()
    {
        // Try to find a recent group with an organizer
        $group = \App\Models\Group::with(['organizer'])
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$group) {
            // Fallback to any group
            $group = \App\Models\Group::with(['organizer'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$group) {
                // Create a mock group with organizer
                $group = (object)[
                    'id' => 9999,
                    'name' => 'Summer Adventure Group 2023',
                    'code' => 'GRP' . strtoupper(Str::random(6)),
                    'status' => 'active',
                    'created_at' => now()->subDays(5),
                    'organizer' => (object)[
                        'id' => 1001,
                        'name' => 'Alex Johnson',
                        'email' => 'alex.j@example.com',
                        'phone' => '+1 (555) 123-4567',
                        'job_title' => 'Group Organizer'
                    ]
                ];
            }
        }

        // Generate a temporary password (in a real scenario, this would be generated by the auth system)
        $tempPassword = strtoupper(Str::random(4)) . rand(1000, 9999) . strtolower(Str::random(3));
        
        // Get the reset token (in a real scenario, this would come from the password reset system)
        $resetToken = Str::random(60);
        $resetUrl = url(route('password.reset', [
            'token' => $resetToken,
            'email' => $group->organizer->email ?? 'user@example.com'
        ], false));

        return [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'code' => $group->code,
                'created_date' => $group->created_at ? $group->created_at->format('l, F j, Y') : now()->format('l, F j, Y')
            ],
            'user' => [
                'name' => $group->organizer->name ?? 'Group Member',
                'email' => $group->organizer->email ?? 'user@example.com',
                'job_title' => $group->organizer->job_title ?? 'Group Member',
                'is_organizer' => true,
                'temporary_password' => $tempPassword,
                'login_url' => url('/login'),
                'reset_password_url' => $resetUrl,
                'support_email' => 'support@barefoot.com',
                'support_phone' => '+1 (800) 123-4567'
            ],
            'security' => [
                'password_requirements' => [
                    'Minimum 12 characters',
                    'At least one uppercase letter',
                    'At least one lowercase letter',
                    'At least one number',
                    'At least one special character (e.g., !@#$%^&*)'
                ],
                'password_tips' => [
                    'Do not share your password with anyone',
                    'Avoid using easily guessable information',
                    'Use a password manager to store your credentials securely',
                    'Enable two-factor authentication for added security'
                ]
            ],
            'next_steps' => [
                'Log in with your temporary password',
                'Change your password immediately after first login',
                'Complete your profile information',
                'Review group details and upcoming events',
                'Download any available trip documents'
            ],
            'expiration' => [
                'temporary_password_expires' => now()->addDays(7)->format('l, F j, Y \a\t g:i A T'),
                'reset_token_expires' => now()->addDays(1)->format('l, F j, Y \a\t g:i A T')
            ],
            'support_contact' => [
                'email' => 'accounts@barefoot.com',
                'phone' => '+1 (800) 555-7890',
                'hours' => 'Monday - Friday, 8 AM - 8 PM EST',
                'emergency_phone' => '+1 (800) 555-1122',
                'contact_person' => 'Account Security Team'
            ]
        ];
    }
    
    /**
     * Get data for NonConfirmedBookingWithConfirmedPayment notification
     * 
     * @return array
     * @throws \Exception
     */
    /**
     * Get data for NonConfirmedBookingWithConfirmedPayment
     * 
     * This notification is sent when a booking has received payment but hasn't been confirmed yet.
     * It typically indicates that additional information or action is required from the client
     * or agent to complete the booking confirmation process.
     *
     * @return array
     * @throws \Exception
     */
    protected function getNonConfirmedBookingWithConfirmedPaymentData()
    {
        $booking = \App\Models\Booking::with([
            'group'
        ])->first();

        return [
            'booking' => $booking,
        ];
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    /**
     * Get data for LastFlightManifestReminder notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getLastFlightManifestReminderData()
    {
        $bookingClient = \App\Models\BookingClient::with([
            'booking.group',
            'guests',
        ])
        ->whereHas('guests')
        ->whereHas('booking.group', function($q) {
            $q->whereNotNull('transportation_submit_before');
        })
        ->first();

        return [
            'bookingClient' => $bookingClient,
            'guestsWithoutManifests' => $bookingClient->guests,
        ];
    }
    
    /**
     * Format duration between two dates
     * 
     * @param string|\Carbon\Carbon $start
     * @param string|\Carbon\Carbon $end
     * @return string
     */
    protected function formatDuration($start, $end)
    {
        $start = $start instanceof \Carbon\Carbon ? $start : now()->parse($start);
        $end = $end instanceof \Carbon\Carbon ? $end : now()->parse($end);
        
        $diff = $start->diff($end);
        $parts = [];
        
        if ($diff->d > 0) $parts[] = $diff->d . 'd';
        if ($diff->h > 0) $parts[] = $diff->h . 'h';
        if ($diff->i > 0) $parts[] = $diff->i . 'm';
        
        return implode(' ', $parts) ?: '0m';
    }
    
    /**
     * Get passport expiry status
     * 
     * @param string|null $expiryDate
     * @return array
     */
    protected function getPassportExpiryStatus($expiryDate)
    {
        if (!$expiryDate) return ['status' => 'missing', 'class' => 'danger', 'label' => 'Not provided'];
        
        $expiry = now()->parse($expiryDate);
        $sixMonthsFromNow = now()->addMonths(6);
        $oneYearFromNow = now()->addYear();
        
        if ($expiry->isPast()) {
            return ['status' => 'expired', 'class' => 'danger', 'label' => 'Expired'];
        } elseif ($expiry->isBefore($sixMonthsFromNow)) {
            return ['status' => 'expiring_soon', 'class' => 'warning', 'label' => 'Expires soon'];
        } elseif ($expiry->isBefore($oneYearFromNow)) {
            return ['status' => 'valid_but_short', 'class' => 'info', 'label' => 'Valid (less than 1 year)'];
        } else {
            return ['status' => 'valid', 'class' => 'success', 'label' => 'Valid'];
        }
    }
    
    /**
     * Get the appropriate notifiable entity for a notification
     *
     * @param string $className
     * @return mixed
     */
    protected function getNotifiable($className)
    {
        switch ($className) {
            case 'App\\Notifications\\BalanceDueDateReminder':
                return $this->getBalanceDueDateReminderNotifiable(); break;

            case 'App\\Notifications\\BookingInvoice':
                    return $this->getBookingInvoiceNotifiable(); break;

            case 'App\\Notifications\\BookingInvoiceFinal':
                return $this->getBookingInvoiceFinalNotifiable(); break;
                
            case 'App\Notifications\BookingReservationCodeNotification':
                return $this->getBookingReservationCodeNotificationNotifiable(); break;                

            case 'App\Notifications\BookingSubmitted':
                return $this->getBookingSubmittedNotifiable(); break; 

            case 'App\Notifications\BookingSubmittedReservationCode':
                return $this->getBookingSubmittedReservationCodeNotifiable(); break; 

            case 'App\Notifications\BookingSubmittedReservationCodeSeperateInvoice':
                return $this->getBookingSubmittedReservationCodeSeperateInvoiceNotifiable(); break;

            case 'App\Notifications\BrideGroupEmail':
                return $this->getBrideGroupEmailNotifiable(); break;

            case 'App\Notifications\CancellationsLastCalls':
                return $this->getCancellationsLastCallsNotifiable(); break;

            case 'App\Notifications\CardDeclined':
                return $this->getCardDeclinedNotifiable(); break;

            case 'App\Notifications\FinalEmail':
                return $this->getFinalEmailNotifiable(); break;

            case 'App\Notifications\FinalFlightManifestReminder':
                return $this->getFinalFlightManifestReminderNotifiable(); break;

            case 'App\Notifications\FitClientPaymentReminder':
                return $this->getFitClientPaymentReminderNotifiable(); break;

            case 'App\Notifications\FitQuoteAccepted':
                return $this->getFitQuoteAcceptedNotifiable(); break;

            case 'App\Notifications\FitQuoteCancelled':
                return $this->getFitQuoteCancelledNotifiable(); break;

            case 'App\Notifications\FitQuoteMail':
                return $this->getFitQuoteMailNotifiable(); break;

            case 'App\Notifications\FitQuoteReminder':
                return $this->getFitQuoteReminderNotifiable(); break;

            case 'App\Notifications\FlightManifestRequest':
                return $this->getFlightManifestRequestNotifiable(); break;

            case 'App\Notifications\FlightManifestSubmitted':
                return $this->getFlightManifestSubmittedNotifiable(); break;

            case 'App\Notifications\GroupEmail':
                return $this->getGroupEmailNotifiable(); break;

            case 'App\Notifications\GroupPasswordNotification':
                return $this->getGroupPasswordNotificationNotifiable(); break;

            case 'App\Notifications\InvoiceMail':
                return $this->getInvoiceMailNotifiable(); break;

            case 'App\Notifications\LastFlightManifestReminder':
                return $this->getLastFlightManifestReminderNotifiable(); break;

            case 'App\Notifications\NonConfirmedBookingWithConfirmedPayment':
                return $this->getNonConfirmedBookingWithConfirmedPaymentNotifiable(); break;

            case 'App\Notifications\OtpNotification':
                return $this->getOtpNotificationNotifiable(); break;

            case 'App\Notifications\PasswordChanged':
                return $this->getPasswordChangedNotifiable(); break;

            case 'App\Notifications\PasswordReset':
                return $this->getPasswordResetNotifiable(); break;

            case 'App\Notifications\PaymentConfirmed':
                return $this->getPaymentConfirmedNotifiable(); break;

            case 'App\Notifications\PaymentDueDateReminder':
                return $this->getPaymentDueDateReminderNotifiable(); break;

            case 'App\Notifications\PaymentInformationUpdatedNotification':
                return $this->getPaymentInformationUpdatedNotificationNotifiable(); break;

            case 'App\Notifications\PaymentSubmitted':
                return $this->getPaymentSubmittedNotifiable(); break;

            case 'App\Notifications\SendCouplesSitePasswordNotification':
                return $this->getSendCouplesSitePasswordNotificationNotifiable(); break;

            case 'App\Notifications\SendGroupPasswordNotification':
                return $this->getSendGroupPasswordNotificationNotifiable(); break;

            case 'App\Notifications\TravelDocumentsMail':
                return $this->getTravelDocumentsMailNotifiable(); break;

            case 'App\Notifications\UserCreated':
                return $this->getUserCreatedNotifiable(); break;
        }
    }
    
    /**
     * Get real data for a notification class
     *
     * @param string $className
     * @return array
     * @throws \Exception
     */
    protected function getDataForNotification($className)
    {
        switch ($className) {
            case 'App\\Notifications\\BalanceDueDateReminder':
                return $this->getBalanceDueDateReminderData(); break;

            case 'App\\Notifications\\BookingInvoice':
                    return $this->getBookingInvoiceData(); break;

            case 'App\\Notifications\\BookingInvoiceFinal':
                return $this->getBookingInvoiceFinalData(); break;
                
            case 'App\Notifications\BookingReservationCodeNotification':
                return $this->getBookingReservationCodeNotificationData(); break;                

            case 'App\Notifications\BookingSubmitted':
                return $this->getBookingSubmittedData(); break; 

            case 'App\Notifications\BookingSubmittedReservationCode':
                return $this->getBookingSubmittedReservationCodeData(); break; 

            case 'App\Notifications\BookingSubmittedReservationCodeSeperateInvoice':
                return $this->getBookingSubmittedReservationCodeSeperateInvoiceData(); break;

            case 'App\Notifications\BrideGroupEmail':
                return $this->getBrideGroupEmailData(); break;

            case 'App\Notifications\CancellationsLastCalls':
                return $this->getCancellationsLastCallsData(); break;

            case 'App\Notifications\CardDeclined':
                return $this->getCardDeclinedData(); break;

            case 'App\Notifications\FinalEmail':
                return $this->getFinalEmailData(); break;

            case 'App\Notifications\FinalFlightManifestReminder':
                return $this->getFinalFlightManifestReminderData(); break;

            case 'App\Notifications\FitClientPaymentReminder':
                return $this->getFitClientPaymentReminderData(); break;

            case 'App\Notifications\FitQuoteAccepted':
                return $this->getFitQuoteAcceptedData(); break;

            case 'App\Notifications\FitQuoteCancelled':
                return $this->getFitQuoteCancelledData(); break;

            case 'App\Notifications\FitQuoteMail':
                return $this->getFitQuoteMailData(); break;

            case 'App\Notifications\FitQuoteReminder':
                return $this->getFitQuoteReminderData(); break;

            case 'App\Notifications\FlightManifestRequest':
                return $this->getFlightManifestRequestData(); break;

            case 'App\Notifications\FlightManifestSubmitted':
                return $this->getFlightManifestSubmittedData(); break;

            case 'App\Notifications\GroupEmail':
                return $this->getGroupEmailData(); break;

            case 'App\Notifications\GroupPasswordNotification':
                return $this->getGroupPasswordNotificationData(); break;

            case 'App\Notifications\InvoiceMail':
                return $this->getInvoiceMailData(); break;

            case 'App\Notifications\LastFlightManifestReminder':
                return $this->getLastFlightManifestReminderData(); break;

            case 'App\Notifications\NonConfirmedBookingWithConfirmedPayment':
                return $this->getNonConfirmedBookingWithConfirmedPaymentData(); break;

            case 'App\Notifications\OtpNotification':
                return $this->getOtpNotificationData(); break;

            case 'App\Notifications\PasswordChanged':
                return $this->getPasswordChangedData(); break;

            case 'App\Notifications\PasswordReset':
                return $this->getPasswordResetData(); break;

            case 'App\Notifications\PaymentConfirmed':
                return $this->getPaymentConfirmedData(); break;

            case 'App\Notifications\PaymentDueDateReminder':
                return $this->getPaymentDueDateReminderData(); break;

            case 'App\Notifications\PaymentInformationUpdatedNotification':
                return $this->getPaymentInformationUpdatedNotificationData(); break;

            case 'App\Notifications\PaymentSubmitted':
                return $this->getPaymentSubmittedData(); break;

            case 'App\Notifications\SendCouplesSitePasswordNotification':
                return $this->getSendCouplesSitePasswordNotificationData(); break;

            case 'App\Notifications\SendGroupPasswordNotification':
                return $this->getSendGroupPasswordNotificationData(); break;

            case 'App\Notifications\TravelDocumentsMail':
                return $this->getTravelDocumentsMailData(); break;

            case 'App\Notifications\UserCreated':
                return $this->getUserCreatedData(); break;
        }
    }
    
    /**
     * Get data for BalanceDueDateReminder notification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getBalanceDueDateReminderData()
    {
        $bookingClient = \App\Models\BookingClient::with(['client', 'guests'])
            ->whereHas('client')
            ->whereHas('guests')
            ->first();

        $client = $bookingClient->client;
        $guest = $bookingClient->guests->first();
        
        return [
            $bookingClient,
            (object)[
                'total' => 1000.00,
                'payments' => 500.00,
                'reservation_code' => $bookingClient->reservation_code
            ],
            $guest->check_in
        ];
    }
    
    /**
     * Get data for BookingInvoice and BookingInvoiceFinal notifications
     * 
     * @return array
     * @throws \Exception
     */
    protected function getBookingInvoiceData()
    {
        // First try to find a booking client with all required relationships and a reservation code
        $bookingClient = \App\Models\BookingClient::with([
            'client',
            'booking.group',
            'booking.clients',
            'booking.clients.client',
            'guests',
            'booking.payments'
        ])
        ->whereHas('booking', function($query) {
            $query->whereHas('group')
                  ->whereHas('clients')
                  ->whereHas('payments');
        })
        ->whereHas('client')
        ->whereNotNull('reservation_code')
        ->whereNotNull('client_id')
        ->first();

        if (!$bookingClient) {
            throw new \Exception('No suitable booking clients found with required relationships for invoice');
        }

        // Ensure we have a reservation code
        if (empty($bookingClient->reservation_code)) {
            $bookingClient->reservation_code = 'INV-' . strtoupper(Str::random(8));
        }

        // Ensure client relationship is loaded
        if (!$bookingClient->relationLoaded('client') || !$bookingClient->client) {
            throw new \Exception('Client information is missing for the selected booking');
        }

        // Ensure we have a reservation code
        if (empty($bookingClient->reservation_code)) {
            throw new \Exception('Selected booking client does not have a reservation code');
        }

        return [$bookingClient];
    }
    
    /**
     * Get data for BookingReservationCodeNotification
     * 
     * @return array
     * @throws \Exception
     */
    protected function getGroupAndReservationCodes()
    {
        $group = \App\Models\Group::with('bookings.clients')
            ->whereHas('bookings.clients')
            ->inRandomOrder()
            ->first();
            
        if (!$group) {
            throw new \Exception('No groups with bookings found in the system');
        }
        
        // Get all reservation codes for the group's bookings
        $reservationCodes = $group->bookings->flatMap(function($booking) {
            return $booking->clients->pluck('reservation_code');
        })->unique()->values()->all();
        
        if (empty($reservationCodes)) {
            throw new \Exception('No reservation codes found for the selected group');
        }
        
        return [$group, collect($reservationCodes)];
    }
    
    /**
     * Get data for OtpNotification
     * 
     * This notification is sent when a user requests a one-time password (OTP)
     * for authentication or verification purposes.
     *
     * @return array
     * @throws \Exception
     */
    protected function getOtpNotificationData()
    {
        // Try to get a real client with recent activity
        $client = \App\Models\Client::whereHas('user', function($query) {
                $query->whereNotNull('email_verified_at')
                      ->where('active', true);
            })
            ->with('user')
            ->inRandomOrder()
            ->first();

        // If no real client found, generate mock data
        if (!$client) {
            $client = (object)[
                'id' => 12345,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'phone' => '+1 (555) 123-4567',
                'user' => (object)[
                    'email' => 'john.doe@example.com',
                    'name' => 'John Doe'
                ]
            ];
        }

        // Generate a 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Set expiration time (15 minutes from now)
        $expiresAt = now()->addMinutes(15);
        
        // Determine the purpose of the OTP (for display purposes)
        $purpose = 'account verification';
        $action = 'verify your identity';
        
        // If we have a real client, we could check their last action to determine purpose
        if (isset($client->last_login_at)) {
            $purpose = 'secure login';
            $action = 'complete your login';
        }

        return [
            'otp' => $otp,
            'recipient' => [
                'name' => $client->user->name ?? $client->first_name . ' ' . $client->last_name,
                'email' => $client->user->email ?? $client->email,
                'phone' => $client->phone ?? null,
            ],
            'purpose' => $purpose,
            'action' => $action,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'expires_in_minutes' => 15,
            'device_info' => [
                'ip' => request()->ip() ?? '192.168.1.1',
                'browser' => request()->header('User-Agent') ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'location' => 'New York, NY, USA', // This would typically come from a geolocation service
            ],
            'security' => [
                'is_trusted_device' => false,
                'requires_additional_verification' => true,
                'verification_methods' => ['email', 'sms'],
            ],
            'support' => [
                'contact_email' => config('emails.support'),
                'contact_phone' => '+1 (800) 123-4567',
                'hours' => 'Monday - Friday, 9:00 AM - 6:00 PM EST',
                'emergency_contact' => '+1 (800) 987-6543',
            ],
            'actions' => [
                'verify' => url('/verify-otp'),
                'resend' => url('/resend-otp'),
                'contact_support' => url('/contact?subject=OTP+Verification+Help'),
                'report_issue' => url('/report-issue?type=otp'),
            ],
            'additional_info' => [
                'is_test' => !isset($client->id),
                'client_id' => $client->id ?? null,
                'timestamp' => now()->toDateTimeString(),
            ]
        ];
    }
    
    /**
     * Get data for PasswordChanged notification
     * 
     * This notification is sent when a user's password has been successfully changed.
     * It provides details about the password change event for security awareness.
     *
     * @return array
     * @throws \Exception
     */
    protected function getPasswordChangedData()
    {
        // Try to get a real user with recent activity
        $user = \App\Models\User::where('active', true)
            ->whereNotNull('email_verified_at')
            ->inRandomOrder()
            ->first();

        // If no real user found, generate mock data
        if (!$user) {
            $user = (object)[
                'id' => 54321,
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'last_login_at' => now()->subHours(2),
                'last_login_ip' => '203.0.113.42',
                'last_login_location' => 'New York, NY, USA',
                'timezone' => 'America/New_York'
            ];
        }

        $changeTime = now();
        $ipAddress = request()->ip() ?? '192.0.2.1';
        $userAgent = request()->header('User-Agent') ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        
        // Determine if this was a user-initiated change or an admin action
        $changedByAdmin = rand(0, 1) === 1;
        $adminName = $changedByAdmin ? 'Admin User' : null;
        $adminEmail = $changedByAdmin ? config('emails.admin') : null;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_type' => isset($user->client) ? 'Client' : 'Staff',
                'timezone' => $user->timezone ?? 'UTC',
            ],
            'change_details' => [
                'timestamp' => $changeTime->toDateTimeString(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'location' => $this->getLocationFromIp($ipAddress) ?? 'Unknown Location',
                'device_type' => $this->getDeviceType($userAgent),
                'browser' => $this->getBrowserInfo($userAgent),
            ],
            'security' => [
                'changed_by_admin' => $changedByAdmin,
                'admin_name' => $adminName,
                'admin_email' => $adminEmail,
                'requires_password_reset' => false,
                'suspicious_activity' => false,
            ],
            'actions' => [
                'secure_account' => url('/account/security'),
                'review_activity' => url('/account/activity'),
                'contact_support' => url('/contact?subject=Password+Change+Inquiry'),
                'report_issue' => url('/report/security?type=password'),
            ],
            'support' => [
                'contact_email' => config('emails.security'),
                'contact_phone' => '+1 (800) 123-4567',
                'hours' => '24/7',
                'emergency_contact' => '+1 (800) 987-6543',
            ],
            'additional_info' => [
                'is_test' => !isset($user->id),
                'previous_password_change' => isset($user->password_changed_at) 
                    ? $user->password_changed_at->diffForHumans() 
                    : 'Over 90 days ago',
            ]
        ];
    }
    
    /**
     * Get data for PasswordReset notification
     * 
     * This notification is sent when a user requests a password reset.
     * It includes a secure reset link and information about the request.
     *
     * @return array
     * @throws \Exception
     */
    protected function getPasswordResetData()
    {
        // Try to get a real user with recent activity
        $user = \App\Models\User::where('active', true)
            ->whereNotNull('email_verified_at')
            ->inRandomOrder()
            ->first();

        // If no real user found, generate mock data
        if (!$user) {
            $user = (object)[
                'id' => 67890,
                'name' => 'Alex Johnson',
                'email' => 'alex.johnson@example.com',
                'timezone' => 'America/Chicago'
            ];
        }

        // Generate a password reset token (in a real scenario, this would come from Laravel's Password Broker)
        $token = str_random(64);
        $expiresAt = now()->addMinutes(config('auth.passwords.users.expire', 60));
        $ipAddress = request()->ip() ?? '192.0.2.1';
        $userAgent = request()->header('User-Agent') ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'timezone' => $user->timezone ?? 'UTC',
            ],
            'reset_details' => [
                'reset_url' => $resetUrl,
                'token' => $token,
                'expires_at' => $expiresAt->toDateTimeString(),
                'expires_in_minutes' => $expiresAt->diffInMinutes(now()),
                'requested_at' => now()->toDateTimeString(),
                'ip_address' => $ipAddress,
                'location' => $this->getLocationFromIp($ipAddress) ?? 'Unknown Location',
                'device_type' => $this->getDeviceType($userAgent),
                'browser' => $this->getBrowserInfo($userAgent),
            ],
            'security' => [
                'is_secure' => true,
                'is_one_time_use' => true,
                'requires_verification' => true,
                'suspicious_activity' => false,
            ],
            'actions' => [
                'reset_password' => $resetUrl,
                'cancel_request' => url(route('password.cancel', ['token' => $token], false)),
                'contact_support' => url('/contact?subject=Password+Reset+Help'),
                'report_unauthorized' => url('/report/security?type=password_reset'),
            ],
            'support' => [
                'contact_email' => config('emails.security'),
                'contact_phone' => '+1 (800) 123-4567',
                'hours' => '24/7',
                'emergency_contact' => '+1 (800) 987-6543',
            ],
            'additional_info' => [
                'is_test' => !isset($user->id),
                'password_last_changed' => isset($user->password_changed_at) 
                    ? $user->password_changed_at->diffForHumans() 
                    : 'Over 90 days ago',
                'account_created_at' => isset($user->created_at) 
                    ? $user->created_at->format('M j, Y') 
                    : 'Unknown',
            ]
        ];
    }
    
    /**
     * Get data for PaymentConfirmed notification
     * 
     * This notification is sent when a payment has been successfully processed.
     * It provides details about the payment and booking information.
     *
     * @return array
     * @throws \Exception
     */
    protected function getPaymentConfirmedData()
    {
        // Try to get a real payment with related booking and client
        $payment = \App\Models\Payment::with([
                'booking',
                'booking.clients',
                'booking.clients.client',
                'booking.payments' => function($query) {
                    $query->where('status', 'completed')
                          ->orderBy('created_at', 'desc');
                },
                'paymentMethod'
            ])
            ->where('status', 'completed')
            ->inRandomOrder()
            ->first();

        // If no real payment found, generate mock data
        if (!$payment) {
            $booking = (object)[
                'id' => 'BK' . rand(10000, 99999),
                'booking_number' => 'BK-' . strtoupper(uniqid()),
                'total_amount' => 2500.00,
                'currency' => 'USD',
                'balance_due' => 0.00,
                'deposit_due_date' => now()->addDays(7)->format('Y-m-d'),
                'final_payment_due_date' => now()->addMonths(3)->format('Y-m-d'),
                'travel_dates' => now()->addMonths(4)->format('M j, Y') . ' - ' . now()->addMonths(4)->addDays(7)->format('M j, Y'),
                'destination' => 'Punta Cana, Dominican Republic',
                'clients' => collect([
                    (object)[
                        'client' => (object)[
                            'first_name' => 'Sarah',
                            'last_name' => 'Williams',
                            'email' => 'sarah.williams@example.com',
                            'phone' => '+1 (555) 987-6543'
                        ]
                    ]
                ])
            ];

            $payment = (object)[
                'id' => 'PYM' . rand(10000, 99999),
                'transaction_id' => 'TXN' . strtoupper(uniqid()),
                'amount' => 1250.00,
                'currency' => 'USD',
                'status' => 'completed',
                'payment_date' => now()->format('Y-m-d H:i:s'),
                'payment_method' => 'credit_card',
                'payment_method_display' => 'Visa ending in 4242',
                'payment_note' => 'Initial deposit payment',
                'booking' => $booking,
                'paymentMethod' => (object)[
                    'type' => 'credit_card',
                    'last_four' => '4242',
                    'brand' => 'visa',
                    'exp_month' => '12',
                    'exp_year' => '2025'
                ]
            ];

            $booking->payments = collect([$payment]);
        }

        $booking = $payment->booking;
        $primaryClient = $booking->clients->first()->client ?? (object)[];
        $totalPaid = $booking->payments->sum('amount');
        $isFinalPayment = $totalPaid >= $booking->total_amount;
        $nextPaymentDue = $isFinalPayment ? null : (
            $totalPaid < ($booking->total_amount * 0.5) 
                ? $booking->deposit_due_date 
                : $booking->final_payment_due_date
        );

        return [
            'payment' => [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => number_format($payment->amount, 2),
                'currency' => $payment->currency,
                'status' => $payment->status,
                'payment_date' => $payment->payment_date,
                'payment_method' => $payment->payment_method_display ?? $payment->payment_method,
                'payment_note' => $payment->payment_note ?? '',
                'receipt_number' => 'RCPT-' . strtoupper(uniqid()),
                'card_details' => $payment->paymentMethod ? [
                    'last_four' => $payment->paymentMethod->last_four,
                    'brand' => ucfirst($payment->paymentMethod->brand),
                    'expiry' => $payment->paymentMethod->exp_month . '/' . substr($payment->paymentMethod->exp_year, -2)
                ] : null
            ],
            'booking' => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'total_amount' => number_format($booking->total_amount, 2),
                'currency' => $booking->currency,
                'amount_paid' => number_format($totalPaid, 2),
                'balance_due' => number_format($booking->balance_due ?? ($booking->total_amount - $totalPaid), 2),
                'is_final_payment' => $isFinalPayment,
                'next_payment_due' => $nextPaymentDue,
                'travel_dates' => $booking->travel_dates,
                'destination' => $booking->destination
            ],
            'client' => [
                'name' => trim(($primaryClient->first_name ?? '') . ' ' . ($primaryClient->last_name ?? '')),
                'email' => $primaryClient->email ?? 'client@example.com',
                'phone' => $primaryClient->phone ?? ''
            ],
            'actions' => [
                'view_booking' => url("/bookings/{$booking->id}"),
                'download_receipt' => url("/payments/{$payment->id}/receipt"),
                'contact_support' => url('/contact?subject=Payment+Confirmation+Help'),
                'view_payment_history' => url("/bookings/{$booking->id}/payments")
            ],
            'support' => [
                'contact_email' => config('emails.payments'),
                'contact_phone' => '+1 (800) 555-1234',
                'hours' => 'Monday - Friday, 9:00 AM - 6:00 PM EST',
                'emergency_contact' => '+1 (800) 987-6543',
            ],
            'additional_info' => [
                'is_test' => !isset($payment->id),
                'confirmation_number' => 'CONF-' . strtoupper(uniqid()),
                'thank_you_note' => 'Thank you for your payment! ' . 
                    ($isFinalPayment 
                        ? 'Your booking is now fully paid. We\'re excited to welcome you soon!' 
                        : 'Your next payment is due on ' . $nextPaymentDue . '.')
            ]
        ];
    }
    
    /**
     * Get data for PaymentDueDateReminder notification
     * 
     * This notification is sent to remind clients about upcoming or overdue payments.
     * It includes payment details, due dates, and payment options.
     *
     * @return array
     * @throws \Exception
     */
    protected function getPaymentDueDateReminderData()
    {
        $dueDate = new \App\Models\DueDate();
        $dueDate->group_id = \App\Models\Group::first()->id;
        $dueDate->date = now()->addWeeks(2);
        $dueDate->amount = rand(500, 5000);
        $dueDate->type = 'nights';
        
        return [
            'params' =>(object) [
                'reservation_code' => strtoupper(Str::random(8)),
                'amount' => rand(500, 5000),
            ],
            'dueDate' => $dueDate
        ];
    }
    
    /**
     * Get data for PaymentInformationUpdatedNotification
     * 
     * This notification is sent when payment information is updated in the system.
     * It provides details about the changes made to the payment information.
     *
     * @return array
     * @throws \Exception
     */
    protected function getPaymentInformationUpdatedNotificationData()
    {
        // Try to get a real payment with related booking and client
        $payment = \App\Models\Payment::with([
                'booking',
                'booking.clients.client',
                'paymentMethod',
                'updatedBy'
            ])
            ->whereNotNull('updated_at')
            ->whereColumn('created_at', '!=', 'updated_at')
            ->inRandomOrder()
            ->first();

        // If no real payment found, generate mock data
        if (!$payment) {
            $updatedBy = (object)[
                'id' => 999,
                'name' => 'System Admin',
                'email' => config('emails.admin'),
                'role' => 'admin'
            ];

            $booking = (object)[
                'id' => 'BK' . rand(10000, 99999),
                'booking_number' => 'BK-' . strtoupper(uniqid()),
                'total_amount' => 3200.00,
                'currency' => 'USD',
                'balance_due' => 1200.00,
                'travel_dates' => now()->addMonths(4)->format('M j, Y') . ' - ' . now()->addMonths(4)->addDays(7)->format('M j, Y'),
                'destination' => 'Punta Cana, Dominican Republic',
                'clients' => collect([
                    (object)[
                        'client' => (object)[
                            'first_name' => 'Emily',
                            'last_name' => 'Johnson',
                            'email' => 'emily.johnson@example.com',
                            'phone' => '+1 (555) 456-7890'
                        ]
                    ]
                ])
            ];

            $oldPaymentMethod = (object)[
                'type' => 'credit_card',
                'last_four' => '1111',
                'brand' => 'visa',
                'exp_month' => '06',
                'exp_year' => '2024'
            ];

            $newPaymentMethod = (object)[
                'type' => 'credit_card',
                'last_four' => '4242',
                'brand' => 'mastercard',
                'exp_month' => '12',
                'exp_year' => '2025'
            ];

            $payment = (object)[
                'id' => 'PYM' . rand(10000, 99999),
                'transaction_id' => 'TXN' . strtoupper(uniqid()),
                'amount' => 1000.00,
                'currency' => 'USD',
                'status' => 'pending',
                'payment_date' => now()->format('Y-m-d H:i:s'),
                'payment_method' => 'credit_card',
                'payment_method_display' => 'Mastercard ending in 4242',
                'payment_note' => 'Monthly installment',
                'booking' => $booking,
                'paymentMethod' => $newPaymentMethod,
                'updated_at' => now(),
                'created_at' => now()->subDays(30),
                'updatedBy' => $updatedBy,
                'changes' => [
                    'payment_method' => [
                        'old' => 'Visa ending in 1111',
                        'new' => 'Mastercard ending in 4242'
                    ],
                    'expiry_date' => [
                        'old' => '06/24',
                        'new' => '12/25'
                    ],
                    'status' => [
                        'old' => 'scheduled',
                        'new' => 'pending'
                    ]
                ]
            ];
        } else {
            // For real payments, simulate some changes
            $payment->changes = [
                'amount' => [
                    'old' => number_format($payment->amount * 0.95, 2),
                    'new' => number_format($payment->amount, 2)
                ],
                'payment_date' => [
                    'old' => now()->subDays(2)->format('Y-m-d H:i:s'),
                    'new' => $payment->payment_date
                ],
                'status' => [
                    'old' => 'pending',
                    'new' => $payment->status
                ]
            ];
        }

        $booking = $payment->booking;
        $primaryClient = $booking->clients->first()->client ?? (object)[];
        $updatedBy = $payment->updatedBy ?? (object)['name' => 'System', 'email' => config('emails.system')];
        $updateTime = $payment->updated_at ?? now();

        return [
            'payment' => [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => number_format($payment->amount, 2),
                'currency' => $payment->currency,
                'status' => $payment->status,
                'payment_date' => $payment->payment_date,
                'payment_method' => $payment->payment_method_display ?? $payment->payment_method,
                'payment_note' => $payment->payment_note ?? '',
                'card_details' => $payment->paymentMethod ? [
                    'last_four' => $payment->paymentMethod->last_four,
                    'brand' => ucfirst($payment->paymentMethod->brand),
                    'expiry' => $payment->paymentMethod->exp_month . '/' . substr($payment->paymentMethod->exp_year, -2)
                ] : null
            ],
            'booking' => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'total_amount' => number_format($booking->total_amount, 2),
                'currency' => $booking->currency,
                'balance_due' => number_format($booking->balance_due ?? 0, 2),
                'travel_dates' => $booking->travel_dates ?? 'Not specified',
                'destination' => $booking->destination ?? 'Not specified'
            ],
            'changes' => $payment->changes ?? [],
            'updated_by' => [
                'name' => $updatedBy->name,
                'email' => $updatedBy->email,
                'role' => $updatedBy->role ?? 'system',
                'ip_address' => request()->ip() ?? '192.0.2.1',
                'user_agent' => request()->header('User-Agent') ?? 'System'
            ],
            'client' => [
                'name' => trim(($primaryClient->first_name ?? '') . ' ' . ($primaryClient->last_name ?? '')),
                'email' => $primaryClient->email ?? 'client@example.com',
                'phone' => $primaryClient->phone ?? ''
            ],
            'metadata' => [
                'update_timestamp' => $updateTime->toDateTimeString(),
                'update_reason' => $payment->update_reason ?? 'Payment information updated',
                'is_automatic_update' => $payment->is_automatic_update ?? false,
                'system_version' => config('app.version', '1.0.0')
            ],
            'actions' => [
                'view_payment' => url("/payments/{$payment->id}"),
                'view_booking' => url("/bookings/{$booking->id}"),
                'contact_support' => url('/contact?subject=Payment+Update+Inquiry'),
                'report_issue' => url('/report/issue?type=payment_update')
            ],
            'support' => [
                'contact_email' => config('emails.billing'),
                'contact_phone' => '+1 (800) 555-1234',
                'hours' => 'Monday - Friday, 9:00 AM - 6:00 PM EST',
                'emergency_contact' => '+1 (800) 987-6543',
            ],
            'additional_info' => [
                'is_test' => !isset($payment->id),
                'security_note' => 'If you did not authorize these changes, please contact our support team immediately.',
                'next_steps' => isset($payment->status) && $payment->status === 'failed' 
                    ? 'Please update your payment information to avoid any service interruptions.' 
                    : 'No further action is required at this time.'
            ]
        ];
    }
    
    /**
     * Get data for PaymentSubmitted notification
     * 
     * This notification is sent when a payment is submitted for processing.
     * It provides details about the payment and what to expect next.
     *
     * @return array
     * @throws \Exception
     */
    protected function getPaymentSubmittedData()
    {
        // Try to get a real payment with related booking and client
        $payment = \App\Models\Payment::with([
                'booking',
                'booking.clients.client',
                'paymentMethod',
                'processedBy'
            ])
            ->where('status', 'pending')
            ->orWhere('status', 'processing')
            ->inRandomOrder()
            ->first();

        // If no real payment found, generate mock data
        if (!$payment) {
            $processedBy = (object)[
                'id' => 1001,
                'name' => 'John Smith',
                'email' => 'john.smith@barefootbridal.com',
                'role' => 'agent'
            ];

            $booking = (object)[
                'id' => 'BK' . rand(10000, 99999),
                'booking_number' => 'BK-' . strtoupper(uniqid()),
                'total_amount' => 4500.00,
                'currency' => 'USD',
                'balance_due' => 1500.00,
                'deposit_due_date' => now()->addDays(5)->format('Y-m-d'),
                'final_payment_due_date' => now()->addMonths(2)->format('Y-m-d'),
                'travel_dates' => now()->addMonths(3)->format('M j, Y') . ' - ' . now()->addMonths(3)->addDays(7)->format('M j, Y'),
                'destination' => 'Cancun, Mexico',
                'clients' => collect([
                    (object)[
                        'client' => (object)[
                            'first_name' => 'Jessica',
                            'last_name' => 'Martinez',
                            'email' => 'jessica.martinez@example.com',
                            'phone' => '+1 (555) 123-4567'
                        ]
                    ]
                ])
            ];

            $paymentMethod = (object)[
                'type' => 'credit_card',
                'last_four' => '1881',
                'brand' => 'amex',
                'exp_month' => '03',
                'exp_year' => '2026'
            ];

            $payment = (object)[
                'id' => 'PYM' . rand(10000, 99999),
                'transaction_id' => 'TXN' . strtoupper(uniqid()),
                'amount' => 1500.00,
                'currency' => 'USD',
                'status' => 'processing',
                'payment_date' => now()->format('Y-m-d H:i:s'),
                'payment_method' => 'credit_card',
                'payment_method_display' => 'American Express ending in 1881',
                'payment_note' => 'Final payment for booking',
                'processing_fee' => 45.00,
                'subtotal' => 1455.00,
                'booking' => $booking,
                'paymentMethod' => $paymentMethod,
                'created_at' => now(),
                'processedBy' => $processedBy,
                'reference_number' => 'PAY-' . strtoupper(uniqid()),
                'estimated_processing_time' => '1-2 business days'
            ];
        } else {
            // For real payments, add some calculated fields
            $payment->reference_number = 'PAY-' . strtoupper(substr(md5($payment->id . $payment->created_at), 0, 10));
            $payment->estimated_processing_time = '1-3 business days';
            $payment->processing_fee = $payment->amount * 0.03; // 3% processing fee
            $payment->subtotal = $payment->amount - $payment->processing_fee;
        }

        $booking = $payment->booking;
        $primaryClient = $booking->clients->first()->client ?? (object)[];
        $processedBy = $payment->processedBy ?? (object)['name' => 'System', 'email' => config('emails.system')];
        $submissionTime = $payment->created_at ?? now();

        return [
            'payment' => [
                'id' => $payment->id,
                'reference_number' => $payment->reference_number,
                'transaction_id' => $payment->transaction_id,
                'amount' => number_format($payment->amount, 2),
                'currency' => $payment->currency,
                'status' => $payment->status,
                'payment_date' => $payment->payment_date,
                'payment_method' => $payment->payment_method_display ?? $payment->payment_method,
                'payment_note' => $payment->payment_note ?? '',
                'processing_fee' => number_format($payment->processing_fee ?? 0, 2),
                'subtotal' => number_format($payment->subtotal ?? ($payment->amount - ($payment->processing_fee ?? 0)), 2),
                'card_details' => $payment->paymentMethod ? [
                    'last_four' => $payment->paymentMethod->last_four,
                    'brand' => ucfirst($payment->paymentMethod->brand),
                    'expiry' => $payment->paymentMethod->exp_month . '/' . substr($payment->paymentMethod->exp_year, -2)
                ] : null,
                'estimated_processing_time' => $payment->estimated_processing_time ?? '1-3 business days',
                'next_steps' => 'Your payment is being processed. You will receive a confirmation email once it is complete.'
            ],
            'booking' => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'total_amount' => number_format($booking->total_amount, 2),
                'currency' => $booking->currency,
                'balance_due' => number_format($booking->balance_due ?? 0, 2),
                'travel_dates' => $booking->travel_dates ?? 'Not specified',
                'destination' => $booking->destination ?? 'Not specified',
                'next_payment_due' => $booking->final_payment_due_date ?? null,
                'is_final_payment' => ($booking->balance_due ?? 0) <= ($payment->amount ?? 0)
            ],
            'client' => [
                'name' => trim(($primaryClient->first_name ?? '') . ' ' . ($primaryClient->last_name ?? '')),
                'email' => $primaryClient->email ?? 'client@example.com',
                'phone' => $primaryClient->phone ?? ''
            ],
            'processed_by' => [
                'name' => $processedBy->name,
                'email' => $processedBy->email,
                'role' => $processedBy->role ?? 'system',
                'contact_info' => $processedBy->phone ?? $processedBy->email
            ],
            'timeline' => [
                'submitted_at' => $submissionTime->toDateTimeString(),
                'estimated_completion' => $submissionTime->copy()->addWeekday()->toDateTimeString(),
                'last_updated' => now()->toDateTimeString()
            ],
            'actions' => [
                'view_payment' => url("/payments/{$payment->id}"),
                'view_booking' => url("/bookings/{$booking->id}"),
                'contact_support' => url('/contact?subject=Payment+Submission+Help'),
                'download_receipt' => url("/payments/{$payment->id}/receipt")
            ],
            'support' => [
                'contact_email' => config('emails.billing'),
                'contact_phone' => '+1 (800) 555-1234',
                'hours' => 'Monday - Friday, 9:00 AM - 6:00 PM EST',
                'emergency_contact' => '+1 (800) 987-6543',
            ],
            'additional_info' => [
                'is_test' => !isset($payment->id),
                'processing_notes' => 'Most payments are processed within 1-2 business days. You will receive an email confirmation once your payment has been processed.',
                'cancellation_policy' => 'If you need to cancel this payment, please contact our support team immediately. Cancellation may be subject to a fee.'
            ]
        ];
    }
    
    /**
     * Get data for SendCouplesSitePasswordNotification
     * 
     * This notification is sent when a couple's site password is shared with users.
     * It provides secure access details to the couple's wedding website.
     *
     * @return array
     */
    protected function getSendCouplesSitePasswordNotificationData()
    {
        // Try to get a real couple's site with related booking and clients
        $site = \App\Models\CouplesSite::with([
                'booking',
                'booking.clients.client',
                'createdBy'
            ])
            ->whereNotNull('password')
            ->inRandomOrder()
            ->first();

        // If no real site found, generate mock data
        if (!$site) {
            $createdBy = (object)[
                'id' => 1002,
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@barefootbridal.com',
                'role' => 'wedding_planner'
            ];

            $couple = [
                (object)[
                    'first_name' => 'Michael',
                    'last_name' => 'Anderson',
                    'email' => 'michael.anderson@example.com',
                    'phone' => '+1 (555) 123-4567'
                ],
                (object)[
                    'first_name' => 'Jennifer',
                    'last_name' => 'Smith',
                    'email' => 'jennifer.smith@example.com',
                    'phone' => '+1 (555) 987-6543'
                ]
            ];

            $site = (object)[
                'id' => 'SITE' . rand(1000, 9999),
                'url' => 'https://barefootbridal.com/couples/anderson-smith-2025',
                'title' => 'Michael & Jennifer',
                'password' => 'Love2025!',
                'expires_at' => now()->addMonths(12)->format('Y-m-d'),
                'is_active' => true,
                'created_at' => now(),
                'createdBy' => $createdBy,
                'booking' => (object)[
                    'id' => 'BK' . rand(10000, 99999),
                    'booking_number' => 'BK-' . strtoupper(uniqid()),
                    'wedding_date' => now()->addMonths(6)->format('F j, Y'),
                    'destination' => 'Maui, Hawaii',
                    'clients' => collect([
                        (object)['client' => $couple[0]],
                        (object)['client' => $couple[1]]
                    ])
                ]
            ];
        } else {
            $couple = $site->booking ? $site->booking->clients->pluck('client')->all() : [];
        }

        $coupleNames = [];
        $primaryEmail = '';
        $primaryPhone = '';
        
        if (!empty($couple) && is_array($couple)) {
            foreach ($couple as $partner) {
                if ($partner) {
                    $coupleNames[] = trim(($partner->first_name ?? '') . ' ' . ($partner->last_name ?? ''));
                    if (empty($primaryEmail) && !empty($partner->email)) {
                        $primaryEmail = $partner->email;
                    }
                    if (empty($primaryPhone) && !empty($partner->phone)) {
                        $primaryPhone = $partner->phone;
                    }
                }
            }
        }

        $coupleName = !empty($coupleNames) ? implode(' & ', $coupleNames) : 'The Couple';
        $weddingDate = $site->booking->wedding_date ?? 'TBD';
        $destination = $site->booking->destination ?? 'Destination TBD';
        $expiryDate = $site->expires_at ? (is_string($site->expires_at) ? $site->expires_at : $site->expires_at->format('F j, Y')) : '1 year from now';

        return [
            'site' => [
                'id' => $site->id,
                'url' => $site->url,
                'title' => $site->title ?? $coupleName . "'s Wedding",
                'password' => $site->password,
                'expires_at' => $expiryDate,
                'is_active' => $site->is_active ?? true,
                'created_at' => isset($site->created_at) ? (is_string($site->created_at) ? $site->created_at : $site->created_at->format('F j, Y')) : now()->format('F j, Y'),
                'created_by' => $site->createdBy->name ?? 'Your Wedding Planner'
            ],
            'couple' => [
                'names' => $coupleName,
                'email' => $primaryEmail ?: config('emails.contact'),
                'phone' => $primaryPhone ?: '+1 (800) 555-1234',
                'wedding_date' => $weddingDate,
                'destination' => $destination
            ],
            'access_instructions' => [
                '1. Visit the couple\'s website: ' . ($site->url ?? 'https://barefootbridal.com/couples/[couple-id]'),
                '2. Enter the password when prompted',
                '3. Browse the site to view wedding details, photos, and more'
            ],
            'security_info' => [
                'keep_secure' => 'Please keep this password confidential and do not share it publicly.',
                'change_password' => 'You can change your password at any time from the site settings.',
                'report_issues' => 'If you suspect any unauthorized access, please contact us immediately.'
            ],
            'features' => [
                'Photo Gallery',
                'Wedding Details',
                'RSVP Management',
                'Gift Registry',
                'Travel Information',
                'Event Schedule'
            ],
            'actions' => [
                'visit_site' => $site->url ?? 'https://barefootbridal.com/couples/[couple-id]',
                'contact_couple' => 'mailto:' . ($primaryEmail ?: config('emails.contact')),
                'contact_support' => 'https://barefootbridal.com/contact?subject=Couples+Site+Support',
                'forgot_password' => 'https://barefootbridal.com/password-reset'
            ],
            'support' => [
                'email' => config('emails.support'),
                'phone' => '+1 (800) 555-1234',
                'hours' => 'Monday - Friday, 9:00 AM - 6:00 PM EST',
                'emergency_contact' => true
            ],
            'additional_info' => [
                'is_test' => !isset($site->id),
                'last_updated' => now()->format('F j, Y \a\t g:i A T'),
                'ip_address' => request()->ip() ?? 'Not available',
                'user_agent' => request()->header('User-Agent') ?? 'Unknown browser'
            ]
        ];
    }
    
    /**
     * Get data for SendGroupPasswordNotification
     * 
     * This notification is sent when a group travel password is shared with travelers.
     * It provides secure access details to the group travel portal.
     *
     * @return array
     */
    protected function getSendGroupPasswordNotificationData()
    {
        // Try to get a real group with related booking and travelers
        $group = \App\Models\Group::with([
                'booking',
                'booking.clients.client',
                'createdBy',
                'destination',
                'itinerary'
            ])
            ->whereNotNull('access_password')
            ->inRandomOrder()
            ->first();

        // If no real group found, generate mock data
        if (!$group) {
            $createdBy = (object)[
                'id' => 1003,
                'name' => 'Alex Rodriguez',
                'email' => 'alex.rodriguez@barefootbridal.com',
                'role' => 'group_travel_specialist',
                'phone' => '+1 (800) 555-9876'
            ];

            $travelers = collect([
                (object)[
                    'first_name' => 'Robert',
                    'last_name' => 'Wilson',
                    'email' => 'robert.wilson@example.com',
                    'phone' => '+1 (555) 123-7890',
                    'is_primary' => true
                ],
                (object)[
                    'first_name' => 'Maria',
                    'last_name' => 'Garcia',
                    'email' => 'maria.garcia@example.com',
                    'phone' => '+1 (555) 456-1234',
                    'is_primary' => false
                ]
            ]);

            $group = (object)[
                'id' => 'GRP' . rand(1000, 9999),
                'name' => 'Smith-Wilson Wedding Group',
                'access_url' => 'https://barefootbridal.com/groups/smith-wilson-2025',
                'access_password' => 'Aloha2025!',
                'access_expires_at' => now()->addMonths(9)->format('Y-m-d'),
                'is_active' => true,
                'created_at' => now(),
                'createdBy' => $createdBy,
                'destination' => (object)[
                    'name' => 'Maui, Hawaii',
                    'resort_name' => 'Grand Wailea, A Waldorf Astoria Resort',
                    'check_in' => now()->addMonths(6)->format('Y-m-d'),
                    'check_out' => now()->addMonths(6)->addDays(7)->format('Y-m-d')
                ],
                'itinerary' => (object)[
                    'highlights' => [
                        'Welcome Luau Dinner',
                        'Snorkeling Excursion',
                        'Sunset Cruise',
                        'Farewell Brunch'
                    ]
                ],
                'booking' => (object)[
                    'id' => 'BK' . rand(10000, 99999),
                    'booking_number' => 'BK-' . strtoupper(uniqid()),
                    'travel_dates' => now()->addMonths(6)->format('M j, Y') . ' - ' . now()->addMonths(6)->addDays(7)->format('M j, Y'),
                    'clients' => $travelers->map(function($traveler) {
                        return (object)['client' => $traveler];
                    })
                ]
            ];
        } else {
            $travelers = $group->booking ? $group->booking->clients->pluck('client')->all() : [];
        }

        $primaryTraveler = collect($travelers)->firstWhere('is_primary', true) ?? ($travelers[0] ?? null);
        $groupName = $group->name ?? 'Your Group Travel';
        $destination = $group->destination->name ?? 'Destination TBD';
        $travelDates = $group->booking->travel_dates ?? 'Dates TBD';
        $expiryDate = $group->access_expires_at ? (is_string($group->access_expires_at) ? $group->access_expires_at : $group->access_expires_at->format('F j, Y')) : '90 days after travel';

        return [
            'group' => [
                'id' => $group->id,
                'name' => $groupName,
                'access_url' => $group->access_url,
                'access_password' => $group->access_password,
                'access_expires_at' => $expiryDate,
                'is_active' => $group->is_active ?? true,
                'created_at' => isset($group->created_at) ? (is_string($group->created_at) ? $group->created_at : $group->created_at->format('F j, Y')) : now()->format('F j, Y'),
                'created_by' => $group->createdBy->name ?? 'Your Travel Specialist'
            ],
            'trip' => [
                'destination' => $destination,
                'resort_name' => $group->destination->resort_name ?? 'Resort TBD',
                'check_in' => isset($group->destination->check_in) ? (is_string($group->destination->check_in) ? $group->destination->check_in : $group->destination->check_in->format('F j, Y')) : 'TBD',
                'check_out' => isset($group->destination->check_out) ? (is_string($group->destination->check_out) ? $group->destination->check_out : $group->destination->check_out->format('F j, Y')) : 'TBD',
                'travel_dates' => $travelDates,
                'itinerary_highlights' => $group->itinerary->highlights ?? []
            ],
            'primary_contact' => $primaryTraveler ? [
                'name' => trim(($primaryTraveler->first_name ?? '') . ' ' . ($primaryTraveler->last_name ?? '')),
                'email' => $primaryTraveler->email ?? config('emails.groups'),
                'phone' => $primaryTraveler->phone ?? '+1 (800) 555-1234'
            ] : null,
            'access_instructions' => [
                '1. Visit the group travel portal: ' . ($group->access_url ?? 'https://barefootbridal.com/groups/[group-id]'),
                '2. Enter the access password when prompted',
                '3. Explore your itinerary, travel details, and group activities'
            ],
            'security_info' => [
                'keep_secure' => 'This password is for group members only. Please do not share it publicly.',
                'password_strength' => 'Your password meets our security standards.',
                'report_issues' => 'If you suspect any unauthorized access, please contact us immediately.'
            ],
            'portal_features' => [
                'View and manage your booking',
                'Access detailed itinerary',
                'View group activities and events',
                'Make payments and view balance',
                'Connect with other travelers',
                'Access travel documents',
                'View packing lists and travel tips'
            ],
            'actions' => [
                'visit_portal' => $group->access_url ?? 'https://barefootbridal.com/groups/[group-id]',
                'contact_group_leader' => $primaryTraveler ? 'mailto:' . $primaryTraveler->email : 'mailto:'.config('emails.groups').'',
                'contact_support' => 'https://barefootbridal.com/contact?subject=Group+Travel+Support',
                'reset_password' => 'https://barefootbridal.com/password-reset/group',
                'download_mobile_app' => 'https://barefootbridal.com/app-download'
            ],
            'support' => [
                'email' => config('emails.groups'),
                'phone' => '+1 (800) 555-1234',
                'hours' => '24/7 Emergency Support Available',
                'emergency_contact' => true,
                'group_travel_specialist' => $group->createdBy->name ?? 'Your Group Travel Specialist',
                'specialist_contact' => $group->createdBy->email ?? config('emails.groups')
            ],
            'additional_info' => [
                'is_test' => !isset($group->id),
                'last_updated' => now()->format('F j, Y \a\t g:i A T'),
                'ip_address' => request()->ip() ?? 'Not available',
                'user_agent' => request()->header('User-Agent') ?? 'Unknown browser',
                'travel_insurance' => 'We strongly recommend purchasing travel insurance for your trip.'
            ]
        ];
    }
    
    /**
     * Get data for TravelDocumentsMail notification
     * 
     * This notification is sent when travel documents are ready for a booking.
     * It provides access to important travel documents and information.
     *
     * @return array
     */
    protected function getTravelDocumentsMailData()
    {
        // Try to get a real booking with related clients and documents
        $booking = \App\Models\Booking::with([
                'clients.client',
                'documents',
                'flights',
                'accommodations',
                'transfers',
                'activities'
            ])
            ->whereHas('documents')
            ->inRandomOrder()
            ->first();

        // If no real booking found, generate mock data
        if (!$booking) {
            $clients = collect([
                (object)[
                    'first_name' => 'Daniel',
                    'last_name' => 'Martinez',
                    'email' => 'daniel.martinez@example.com',
                    'phone' => '+1 (555) 123-4567',
                    'passport_number' => 'A12345678',
                    'date_of_birth' => '1985-07-15'
                ],
                (object)[
                    'first_name' => 'Sophia',
                    'last_name' => 'Martinez',
                    'email' => 'sophia.martinez@example.com',
                    'phone' => '+1 (555) 987-6543',
                    'passport_number' => 'B87654321',
                    'date_of_birth' => '1988-11-22'
                ]
            ]);

            $booking = (object)[
                'id' => 'BK' . rand(10000, 99999),
                'booking_number' => 'BK-' . strtoupper(uniqid()),
                'travel_dates' => now()->addWeeks(2)->format('M j, Y') . ' - ' . now()->addWeeks(3)->format('M j, Y'),
                'destination' => 'Punta Cana, Dominican Republic',
                'status' => 'confirmed',
                'balance_due' => 0.00,
                'total_amount' => 3250.00,
                'currency' => 'USD',
                'created_at' => now()->subMonths(3),
                'documents' => collect([
                    (object)[
                        'id' => 'DOC' . rand(1000, 9999),
                        'name' => 'E-Ticket and Itinerary',
                        'type' => 'itinerary',
                        'file_url' => 'https://barefootbridal.com/documents/eticket-' . strtolower(uniqid()) . '.pdf',
                        'expires_at' => now()->addMonths(6),
                        'is_required' => true
                    ],
                    (object)[
                        'id' => 'DOC' . rand(1000, 9999),
                        'name' => 'Hotel Voucher',
                        'type' => 'voucher',
                        'file_url' => 'https://barefootbridal.com/documents/hotel-voucher-' . strtolower(uniqid()) . '.pdf',
                        'expires_at' => now()->addMonths(6),
                        'is_required' => true
                    ],
                    (object)[
                        'id' => 'DOC' . rand(1000, 9999),
                        'name' => 'Travel Insurance Policy',
                        'type' => 'insurance',
                        'file_url' => 'https://barefootbridal.com/documents/insurance-' . strtolower(uniqid()) . '.pdf',
                        'expires_at' => now()->addYears(1),
                        'is_required' => false
                    ]
                ]),
                'flights' => collect([
                    (object)[
                        'airline' => 'American Airlines',
                        'flight_number' => 'AA1234',
                        'departure_airport' => 'MIA',
                        'arrival_airport' => 'PUJ',
                        'departure_time' => now()->addWeeks(2)->setTime(8, 30),
                        'arrival_time' => now()->addWeeks(2)->setTime(10, 15),
                        'confirmation_code' => 'ABC123'
                    ]
                ]),
                'accommodations' => collect([
                    (object)[
                        'name' => 'Excellence Punta Cana',
                        'check_in' => now()->addWeeks(2)->format('Y-m-d'),
                        'check_out' => now()->addWeeks(3)->format('Y-m-d'),
                        'room_type' => 'Excellence Club Junior Suite Ocean View',
                        'confirmation_number' => 'RES' . rand(10000, 99999)
                    ]
                ]),
                'clients' => $clients->map(function($client) {
                    return (object)['client' => $client];
                })
            ];
        }

        $primaryClient = $booking->clients->first()->client ?? (object)[];
        $travelers = $booking->clients->map(function($item) {
            return $item->client;
        });

        $departureDate = $booking->flights->first()->departure_time ?? now()->addWeeks(2);
        $daysUntilTravel = now()->diffInDays($departureDate);

        return [
            'booking' => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'status' => $booking->status,
                'travel_dates' => $booking->travel_dates,
                'destination' => $booking->destination,
                'balance_due' => number_format($booking->balance_due, 2),
                'total_amount' => number_format($booking->total_amount, 2),
                'currency' => $booking->currency,
                'days_until_travel' => $daysUntilTravel,
                'is_fully_paid' => $booking->balance_due <= 0,
                'created_at' => isset($booking->created_at) ? (is_string($booking->created_at) ? $booking->created_at : $booking->created_at->format('F j, Y')) : now()->format('F j, Y')
            ],
            'travelers' => $travelers->map(function($traveler) {
                return [
                    'name' => trim(($traveler->first_name ?? '') . ' ' . ($traveler->last_name ?? '')),
                    'passport_number' => $traveler->passport_number ?? 'Not provided',
                    'date_of_birth' => $traveler->date_of_birth ?? 'Not provided',
                    'contact' => [
                        'email' => $traveler->email ?? 'No email',
                        'phone' => $traveler->phone ?? 'No phone'
                    ]
                ];
            })->toArray(),
            'documents' => $booking->documents->map(function($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'type' => $doc->type,
                    'file_url' => $doc->file_url,
                    'expires_at' => isset($doc->expires_at) ? (is_string($doc->expires_at) ? $doc->expires_at : $doc->expires_at->format('F j, Y')) : 'N/A',
                    'is_required' => $doc->is_required ?? true,
                    'download_url' => $doc->file_url . '?download=1'
                ];
            })->toArray(),
            'itinerary' => [
                'flights' => $booking->flights->map(function($flight) {
                    return [
                        'airline' => $flight->airline,
                        'flight_number' => $flight->flight_number,
                        'departure' => [
                            'airport' => $flight->departure_airport,
                            'time' => isset($flight->departure_time) ? (is_string($flight->departure_time) ? $flight->departure_time : $flight->departure_time->format('M j, Y g:i A')) : 'TBD',
                        ],
                        'arrival' => [
                            'airport' => $flight->arrival_airport,
                            'time' => isset($flight->arrival_time) ? (is_string($flight->arrival_time) ? $flight->arrival_time : $flight->arrival_time->format('M j, Y g:i A')) : 'TBD',
                        ],
                        'confirmation_code' => $flight->confirmation_code ?? 'N/A'
                    ];
                })->toArray(),
                'accommodations' => $booking->accommodations->map(function($acc) {
                    return [
                        'name' => $acc->name,
                        'check_in' => $acc->check_in,
                        'check_out' => $acc->check_out,
                        'room_type' => $acc->room_type ?? 'Standard Room',
                        'confirmation_number' => $acc->confirmation_number ?? 'N/A'
                    ];
                })->toArray(),
                'transfers' => $booking->transfers ? $booking->transfers->toArray() : [],
                'activities' => $booking->activities ? $booking->activities->toArray() : []
            ],
            'important_notes' => [
                'Please print all documents and bring them with you.',
                'Check passport validity (must be valid for at least 6 months after return date).',
                'Arrive at the airport at least 3 hours before departure for international flights.',
                'Have your travel insurance information easily accessible.'
            ],
            'actions' => [
                'view_booking' => url("/bookings/{$booking->id}"),
                'download_all_documents' => url("/bookings/{$booking->id}/documents/download-all"),
                'contact_support' => 'https://barefootbridal.com/contact?subject=Travel+Documents+Help',
                'request_changes' => 'https://barefootbridal.com/request-changes?booking=' . $booking->booking_number,
                'check_in_online' => 'https://checkin.barefootbridal.com/' . $booking->booking_number
            ],
            'support' => [
                'email' => config('emails.support'),
                'phone' => '+1 (800) 555-1234',
                'emergency_phone' => '+1 (800) 987-6543',
                'hours' => '24/7 Emergency Support Available',
                'destination_contact' => [
                    'phone' => '+1 (809) 555-1234',
                    'email' => 'puntacana@barefootbridal.com'
                ]
            ],
            'additional_info' => [
                'is_test' => !isset($booking->id),
                'last_updated' => now()->format('F j, Y \a\t g:i A T'),
                'time_until_travel' => now()->diffForHumans($departureDate, true) . ' until departure',
                'travel_insurance' => 'Your travel insurance is active. Policy number: BP' . rand(100000, 999999)
            ]
        ];
    }
    
    /**
     * Get data for UserCreated notification
     * 
     * This notification is sent when a new user account is created.
     * It provides welcome information and account setup instructions.
     *
     * @return array
     */
    protected function getUserCreatedData()
    {
        // Try to get a recently created user with their roles
        $user = \App\Models\User::with(['roles', 'client'])
            ->where('created_at', '>=', now()->subDays(7))
            ->inRandomOrder()
            ->first();

        // If no real user found, generate mock data
        if (!$user) {
            $roles = collect([
                (object)['name' => 'client', 'display_name' => 'Client']
            ]);
            
            $user = (object)[
                'id' => rand(1000, 9999),
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'username' => 'johndoe',
                'created_at' => now(),
                'email_verified_at' => null,
                'is_active' => true,
                'last_login_at' => null,
                'last_login_ip' => null,
                'timezone' => 'America/New_York',
                'roles' => $roles,
                'client' => (object)[
                    'id' => rand(10000, 99999),
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'phone' => '+1 (555) 123-4567',
                    'address' => '123 Main St, Apt 4B',
                    'city' => 'Miami',
                    'state' => 'FL',
                    'zip_code' => '33101',
                    'country' => 'USA',
                    'date_of_birth' => '1985-05-15',
                    'preferred_contact_method' => 'email'
                ]
            ];
            
            $isNewAccount = true;
        } else {
            $isNewAccount = $user->created_at->diffInHours(now()) < 24;
        }

        $loginUrl = url('/login');
        $resetPasswordUrl = url('/password/reset');
        $profileUrl = url('/profile');
        $supportEmail = config('emails.support');
        $supportPhone = '+1 (800) 555-1234';
        $accountType = $user->roles->isNotEmpty() ? $user->roles->first()->display_name : 'User';
        $fullName = $user->client->first_name . ' ' . $user->client->last_name ?? $user->name;
        
        // Generate a verification token if needed
        $verificationToken = $user->email_verified_at ? null : \Illuminate\Support\Str::random(60);
        $verificationUrl = $verificationToken ? url("/email/verify/{$user->id}/{$verificationToken}") : null;
        
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'account_type' => $accountType,
                'is_verified' => (bool)$user->email_verified_at,
                'is_active' => $user->is_active ?? true,
                'created_at' => $user->created_at->format('F j, Y \a\t g:i A T'),
                'timezone' => $user->timezone ?? config('app.timezone')
            ],
            'profile' => $user->client ? [
                'full_name' => $fullName,
                'first_name' => $user->client->first_name,
                'last_name' => $user->client->last_name,
                'phone' => $user->client->phone,
                'address' => [
                    'line1' => $user->client->address,
                    'city' => $user->client->city,
                    'state' => $user->client->state,
                    'zip_code' => $user->client->zip_code,
                    'country' => $user->client->country
                ],
                'date_of_birth' => $user->client->date_of_birth,
                'preferred_contact' => $user->client->preferred_contact_method
            ] : null,
            'account_setup' => [
                'is_new_account' => $isNewAccount,
                'setup_steps' => [
                    '1. Verify your email address',
                    '2. Set a secure password',
                    '3. Complete your profile',
                    '4. Explore your dashboard'
                ],
                'completion_percentage' => $user->email_verified_at ? 25 : 0
            ],
            'verification' => [
                'is_verified' => (bool)$user->email_verified_at,
                'verification_url' => $verificationUrl,
                'verification_code' => $verificationToken,
                'expires_at' => $verificationToken ? now()->addDays(7)->format('F j, Y \a\t g:i A T') : null
            ],
            'security' => [
                'last_login' => $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never',
                'last_ip' => $user->last_login_ip ?? 'N/A',
                'password_set' => (bool)$user->password,
                'two_factor_enabled' => $user->two_factor_secret ? true : false
            ],
            'actions' => [
                'login' => $loginUrl,
                'reset_password' => $resetPasswordUrl,
                'verify_email' => $verificationUrl,
                'edit_profile' => $profileUrl,
                'contact_support' => "mailto:{$supportEmail}",
                'download_app' => 'https://barefootbridal.com/app-download'
            ],
            'support' => [
                'email' => $supportEmail,
                'phone' => $supportPhone,
                'hours' => 'Monday - Friday, 9:00 AM - 6:00 PM EST',
                'emergency_contact' => true,
                'help_center' => 'https://help.barefootbridal.com',
                'live_chat' => 'https://barefootbridal.com/chat'
            ],
            'resources' => [
                'Getting Started Guide' => 'https://help.barefootbridal.com/getting-started',
                'FAQs' => 'https://help.barefootbridal.com/faq',
                'Video Tutorials' => 'https://help.barefootbridal.com/videos',
                'Community Forum' => 'https://community.barefootbridal.com'
            ],
            'additional_info' => [
                'is_test' => !isset($user->id),
                'ip_address' => request()->ip() ?? 'Not available',
                'user_agent' => request()->header('User-Agent') ?? 'Unknown browser',
                'system_version' => config('app.version', '1.0.0'),
                'current_time' => now()->format('F j, Y \a\t g:i A T')
            ]
        ];
    }
    
    /**
     * Helper method to get location from IP address
     * 
     * @param string $ip
     * @return string|null
     */
    protected function getLocationFromIp($ip)
    {
        // In a real implementation, this would call a geolocation service
        // For now, return a mock location based on the IP
        $locations = [
            'New York, NY, USA',
            'Los Angeles, CA, USA',
            'Chicago, IL, USA',
            'Miami, FL, USA',
            'London, UK',
            'Toronto, Canada',
            'Sydney, Australia'
        ];
        
        return $locations[abs(crc32($ip)) % count($locations)];
    }
    
    /**
     * Helper method to determine device type from user agent
     * 
     * @param string $userAgent
     * @return string
     */
    protected function getDeviceType($userAgent)
    {
        if (stripos($userAgent, 'mobile') !== false) {
            return 'Mobile';
        } elseif (stripos($userAgent, 'tablet') !== false) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }
    
    /**
     * Helper method to get browser information from user agent
     * 
     * @param string $userAgent
     * @return string
     */
    protected function getBrowserInfo($userAgent)
    {
        if (stripos($userAgent, 'chrome') !== false) {
            return 'Chrome';
        } elseif (stripos($userAgent, 'firefox') !== false) {
            return 'Firefox';
        } elseif (stripos($userAgent, 'safari') !== false) {
            return 'Safari';
        } elseif (stripos($userAgent, 'edge') !== false) {
            return 'Microsoft Edge';
        } else {
            return 'Unknown Browser';
        }
    }
    
    /**
     * Get default data for other notifications
     * 
     * @param string $className
     * @return array
     * @throws \Exception
     */
    protected function getDefaultNotificationData($className)
    {
        $booking = \App\Models\Booking::with(['clients', 'clients.client', 'clients.guests'])
            ->whereHas('clients')
            ->inRandomOrder()
            ->first();
            
        if (!$booking) {
            throw new \Exception('No bookings found in the system');
        }
        
        // Map notification classes to their constructor parameters
        $constructorMap = [
            'App\\Notifications\\BookingSubmitted' => [$booking],
            'App\\Notifications\\BookingSubmittedReservationCode' => [$booking],
            'App\\Notifications\\BookingSubmittedReservationCodeSeperateInvoice' => [$booking],
            'App\\Notifications\\BrideGroupEmail' => [$booking],
            'App\\Notifications\\CancellationsLastCalls' => [$booking],
            'App\\Notifications\\CardDeclined' => [$booking],
            'App\\Notifications\\FinalEmail' => [$booking],
            'App\\Notifications\\FinalFlightManifestReminder' => [$booking],
            'App\\Notifications\\FitClientPaymentReminder' => [$booking],
            'App\\Notifications\\FitQuoteAccepted' => [$booking],
            'App\\Notifications\\FitQuoteCancelled' => [$booking],
            'App\\Notifications\\FitQuoteMail' => [$booking],
            'App\\Notifications\\FitQuoteReminder' => [$booking],
            'App\\Notifications\\FlightManifestRequest' => [$booking],
            'App\\Notifications\\FlightManifestSubmitted' => [$booking],
            'App\\Notifications\\GroupEmail' => [$booking],
            'App\\Notifications\\GroupPasswordNotification' => [$booking],
            'App\\Notifications\\InvoiceMail' => [$booking],
            'App\\Notifications\\LastFlightManifestReminder' => [$booking],
            'App\\Notifications\\NonConfirmedBookingWithConfirmedPaymentNotification' => [$booking],
            'App\\Notifications\\OtpNotification' => [$booking],
            'App\\Notifications\\PasswordChanged' => [$booking],
            'App\\Notifications\\PaymentConfirmed' => [$booking],
            'App\\Notifications\\PaymentDueDateReminder' => [$booking],
            'App\\Notifications\\PaymentInformationUpdatedNotification' => [$booking],
            'App\\Notifications\\PaymentSubmitted' => [$booking],
            'App\\Notifications\\SendGroupPasswordNotification' => [$booking],
            'App\\Notifications\\TravelDocumentsMail' => [$booking],
            'App\\Notifications\\UserCreated' => [$booking],
        ];

        return $constructorMap[$className] ?? [];
    }
}
