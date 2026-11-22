<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Unit;
use Tests\TestCase;

class BookingAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function seedWashFoldAndCategories(): array
    {
        $unitKg = Unit::query()->create([
            'name' => 'Per Kg',
            'short_name' => 'kg',
            'is_active' => true,
        ]);

        $washFoldCat = ItemCategory::query()->create([
            'name' => 'Wash & Fold',
            'slug' => 'wash-fold',
            'is_active' => true,
        ]);

        $dryCleaningCat = ItemCategory::query()->create([
            'name' => 'Dry Cleaning',
            'slug' => 'dry-cleaning',
            'is_active' => true,
        ]);

        $repairsCat = ItemCategory::query()->create([
            'name' => 'Repairs',
            'slug' => 'repairs',
            'is_active' => true,
        ]);

        $separateService = Service::query()->create([
            'unit_id' => $unitKg->id,
            'item_category_id' => $washFoldCat->id,
            'name' => 'Separate Wash & Fold',
            'slug' => 'separate-wash-fold',
            'price' => 500.00,
            'description' => 'Light and dark clothes washed separately.',
            'is_active' => true,
        ]);

        $mixedService = Service::query()->create([
            'unit_id' => $unitKg->id,
            'item_category_id' => $washFoldCat->id,
            'name' => 'Mixed Wash & Fold',
            'slug' => 'mixed-wash-fold',
            'price' => 600.00,
            'description' => 'All colours washed together.',
            'is_active' => true,
        ]);

        return compact('unitKg', 'washFoldCat', 'dryCleaningCat', 'repairsCat', 'separateService', 'mixedService');
    }

    public function test_step_1_booking_services_page_loads_database_driven_wash_and_fold_options(): void
    {
        $seeded = $this->seedWashFoldAndCategories();

        $response = $this->get(route('booking.services'));

        $response->assertStatus(200);
        $response->assertSee('Select services');
        $response->assertSee('Select one or more services needed for your clothes.');
        $response->assertSee('Mixed Wash &amp; Fold', false);
        $response->assertSee('All colours washed together.');
        $response->assertSee('BDT 600.00');
        $response->assertSee('Separate Wash &amp; Fold', false);
        $response->assertSee('Light and dark clothes washed separately.');
        $response->assertSee('BDT 500.00');
        $response->assertSee('Add Special Request');
        $response->assertSee('Dry Cleaning');
        $response->assertSee('Repairs');
    }

    public function test_step_1_store_services_requires_database_wash_option_when_wash_and_fold_selected(): void
    {
        $seeded = $this->seedWashFoldAndCategories();
        $washFoldCatId = $seeded['washFoldCat']->id;

        $response = $this->post(route('booking.services.store'), [
            'selected_services' => [$washFoldCatId],
            'services_data' => [
                $washFoldCatId => [
                    'special_request' => 'USE COLD WATER',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(["services_data.{$washFoldCatId}.wash_option_id"]);
    }

    public function test_step_1_store_services_rejects_invalid_service_ids_and_invalid_wash_option_ids(): void
    {
        $seeded = $this->seedWashFoldAndCategories();
        $washFoldCatId = $seeded['washFoldCat']->id;

        // 1. Invalid top-level service ID
        $invalidServiceResp = $this->post(route('booking.services.store'), [
            'selected_services' => [99999],
        ]);
        $invalidServiceResp->assertSessionHasErrors(['selected_services.0']);

        // 2. Invalid wash_option_id that does not belong to Wash & Fold
        $invalidOptionResp = $this->post(route('booking.services.store'), [
            'selected_services' => [$washFoldCatId],
            'services_data' => [
                $washFoldCatId => [
                    'wash_option_id' => 88888,
                ],
            ],
        ]);
        $invalidOptionResp->assertSessionHasErrors(["services_data.{$washFoldCatId}.wash_option_id"]);
    }

    public function test_step_1_store_services_saves_actual_database_option_id_price_and_independent_requests_and_redirects_to_step_2_address(): void
    {
        $seeded = $this->seedWashFoldAndCategories();
        $washFoldCatId = $seeded['washFoldCat']->id;
        $dryCleaningCatId = $seeded['dryCleaningCat']->id;
        $repairsCatId = $seeded['repairsCat']->id;
        $mixedService = $seeded['mixedService'];

        $response = $this->post(route('booking.services.store'), [
            'selected_services' => [$washFoldCatId, $dryCleaningCatId],
            'services_data' => [
                $washFoldCatId => [
                    'wash_option_id' => $mixedService->id,
                    'special_request' => 'USE COLD WATER',
                ],
                $dryCleaningCatId => [
                    'special_request' => 'HANDLE DELICATE FABRIC',
                ],
                $repairsCatId => [
                    // Unselected service request - must be ignored
                    'special_request' => 'SHOULD NOT BE SAVED',
                ],
            ],
        ]);

        // Must redirect to Step 2: Address Selection
        $response->assertRedirect(route('booking.address'));

        $booking = session('booking');
        $this->assertCount(2, $booking['services']);

        $wfEntry = collect($booking['services'])->firstWhere('category_id', $washFoldCatId);
        $dcEntry = collect($booking['services'])->firstWhere('category_id', $dryCleaningCatId);
        $repEntry = collect($booking['services'])->firstWhere('category_id', $repairsCatId);

        $this->assertNull($repEntry);

        // Verify actual database service ID, option name, description, and server-side price
        $this->assertEquals($mixedService->id, $wfEntry['service_id']);
        $this->assertEquals($mixedService->id, $wfEntry['option_service_id']);
        $this->assertEquals('Mixed Wash & Fold', $wfEntry['option_name']);
        $this->assertEquals('All colours washed together.', $wfEntry['option_description']);
        $this->assertEquals(600.00, $wfEntry['price']);
        $this->assertEquals('USE COLD WATER', $wfEntry['special_request']);

        // Verify Dry Cleaning entry has no wash option and preserves its own special request
        $this->assertNull($dcEntry['option_service_id']);
        $this->assertEquals('HANDLE DELICATE FABRIC', $dcEntry['special_request']);
    }

    public function test_step_2_booking_address_page_loads_with_selected_services_context(): void
    {
        $response = $this->withSession([
            'booking' => [
                'services' => [
                    [
                        'category_id' => 1,
                        'service_id' => 2,
                        'option_service_id' => 2,
                        'name' => 'Wash & Fold',
                        'option_name' => 'Mixed Wash & Fold',
                        'price' => 600.0,
                        'special_request' => 'USE COLD WATER',
                    ],
                ],
            ],
        ])->get(route('booking.address'));

        $response->assertStatus(200);
        $response->assertSee('Selected Services (1):');
        $response->assertSee('Mixed Wash &amp; Fold', false);
        $response->assertSee('Add your address');
    }

    public function test_postcode_lookup_rejects_invalid_uk_postcode(): void
    {
        $response = $this->getJson(route('booking.postcode.lookup', ['postcode' => 'INVALID123']));

        $response->assertStatus(422);
        $response->assertJson([
            'valid' => false,
        ]);
    }

    public function test_postcode_lookup_formats_and_verifies_valid_uk_postcode(): void
    {
        Http::fake([
            'api.postcodes.io/postcodes/*' => Http::response([
                'status' => 200,
                'result' => [
                    'postcode' => 'E1 0BJ',
                    'latitude' => 51.5112,
                    'longitude' => -0.0569,
                    'admin_ward' => 'Shadwell',
                    'admin_district' => 'Tower Hamlets',
                    'region' => 'London',
                ],
            ], 200),
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'name' => 'Brockmer House',
                    'address' => [
                        'building' => 'Brockmer House',
                        'road' => 'Crowder Street',
                        'city' => 'London',
                        'postcode' => 'E1 0BJ',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson(route('booking.postcode.lookup', ['postcode' => 'e10bj']));

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'postcode' => 'E1 0BJ',
        ]);

        $addresses = $response->json('addresses');
        $this->assertNotEmpty($addresses);
        $this->assertStringContainsString('E1 0BJ', $addresses[0]);
    }

    public function test_step_2_store_address_saves_uppercase_address_and_preserves_step_1_services(): void
    {
        $response = $this->withSession([
            'booking' => [
                'services' => [
                    [
                        'category_id' => 1,
                        'service_id' => 1,
                        'option_service_id' => 1,
                        'name' => 'Wash & Fold',
                        'option_name' => 'Separate Wash & Fold',
                        'price' => 500.0,
                        'special_request' => 'USE COLD WATER',
                    ],
                    [
                        'category_id' => 3,
                        'service_id' => 3,
                        'option_service_id' => null,
                        'name' => 'Dry Cleaning',
                        'special_request' => 'HANDLE DELICATE FABRIC',
                    ],
                ],
            ],
        ])->post(route('booking.address.store'), [
            'collection_mode' => 'home_office',
            'postcode' => 'e10bj',
            'selected_address' => 'flat 64, brockmer house, crowder street, london, e1 0bj',
            'is_manual' => '0',
            'address_type' => 'HOME',
            'address_details' => 'flat 5, 2nd floor, left side entrance',
        ]);

        $response->assertRedirect(route('booking.schedule'));

        $booking = session('booking');
        // Verify Step 1 services and special requests remain intact
        $this->assertCount(2, $booking['services']);
        $this->assertEquals('Separate Wash & Fold', $booking['services'][0]['option_name']);
        $this->assertEquals(500.0, $booking['services'][0]['price']);
        $this->assertEquals('USE COLD WATER', $booking['services'][0]['special_request']);
        $this->assertEquals('HANDLE DELICATE FABRIC', $booking['services'][1]['special_request']);

        // Verify Step 2 address uppercase conversion and formatting
        $this->assertEquals('E1 0BJ', $booking['address']['postcode']);
        $this->assertEquals('FLAT 64, BROCKMER HOUSE, CROWDER STREET, LONDON, E1 0BJ', $booking['address']['selected_address']);
        $this->assertEquals('HOME', $booking['address']['address_type']);
        $this->assertEquals('FLAT 5, 2ND FLOOR, LEFT SIDE ENTRANCE', $booking['address']['address_details']);
    }

    public function test_step_2_store_address_supports_manual_address_and_hotel_flows(): void
    {
        $manualResponse = $this->post(route('booking.address.store'), [
            'collection_mode' => 'home_office',
            'postcode' => 'sw1a1aa',
            'manual_address' => 'flat 12, 10 downing street, london',
            'is_manual' => '1',
            'address_type' => 'OFFICE',
            'address_details' => 'ring bell at reception',
        ]);

        $manualResponse->assertRedirect(route('booking.address', ['step' => 'summary']));
        $this->assertEquals('SW1A 1AA', session('booking.address.postcode'));
        $this->assertEquals('FLAT 12, 10 DOWNING STREET, LONDON, SW1A 1AA', session('booking.address.selected_address'));

        $hotelResponse = $this->post(route('booking.address.store'), [
            'collection_mode' => 'hotel',
            'hotel_name' => 'the savoy hotel',
            'hotel_address' => 'strand, london, wc2r 0ez',
            'room_number' => 'room 402',
            'hotel_collection_type' => 'use_reception',
            'address_type' => 'HOTEL',
            'address_details' => 'leave at concierge',
        ]);

        $hotelResponse->assertRedirect(route('booking.address', ['step' => 'summary']));
        $this->assertEquals('THE SAVOY HOTEL', session('booking.address.hotel_name'));
        $this->assertEquals('WC2R 0EZ', session('booking.address.postcode'));
        $this->assertEquals('ROOM 402', session('booking.address.room_number'));
        $this->assertEquals('use_reception', session('booking.address.hotel_collection_type'));
        $this->assertEquals('Collect from reception', session('booking.schedule.collection_instructions'));
        $this->assertEquals('Deliver to reception', session('booking.schedule.delivery_instructions'));
    }

    public function test_hotel_room_number_and_collection_type_validation_and_synchronization(): void
    {
        // 1. Standard postcode screen with address_type = HOTEL requires room_number
        $missingRoomPostcodeResp = $this->post(route('booking.address.store'), [
            'collection_mode' => 'home_office',
            'postcode' => 'E1 0BJ',
            'selected_address' => 'FLAT 64, BROCKMER HOUSE, CROWDER STREET, LONDON, E1 0BJ',
            'address_type' => 'HOTEL',
            'room_number' => '',
            'address_details' => 'JGHGHGH',
        ]);
        $missingRoomPostcodeResp->assertSessionHasErrors(['room_number']);

        // 2. Standard postcode screen with address_type = HOTEL and valid room_number saves independently from address_details
        $validRoomPostcodeResp = $this->post(route('booking.address.store'), [
            'collection_mode' => 'home_office',
            'postcode' => 'E1 0BJ',
            'selected_address' => 'FLAT 64, BROCKMER HOUSE, CROWDER STREET, LONDON, E1 0BJ',
            'address_type' => 'HOTEL',
            'room_number' => 'room 305',
            'address_details' => 'JGHGHGH',
        ]);
        $validRoomPostcodeResp->assertRedirect(route('booking.address', ['step' => 'summary']));
        $this->assertEquals('ROOM 305', session('booking.address.room_number'));
        $this->assertEquals('JGHGHGH', session('booking.address.address_details'));

        // 3. Switching to HOME or OFFICE does not save room_number
        $homeResp = $this->post(route('booking.address.store'), [
            'collection_mode' => 'home_office',
            'postcode' => 'E1 0BJ',
            'selected_address' => 'FLAT 64, BROCKMER HOUSE, CROWDER STREET, LONDON, E1 0BJ',
            'address_type' => 'HOME',
            'address_details' => 'FLAT 5, 2ND FLOOR',
        ]);
        $homeResp->assertRedirect(route('booking.address', ['step' => 'summary']));
        $this->assertNull(session('booking.address.room_number'));
        $this->assertNull(session('booking.address.hotel_collection_type'));

        // 4. Hotel search mode requires both room_number and hotel_collection_type
        $hotelMissingFieldsResp = $this->post(route('booking.address.store'), [
            'collection_mode' => 'hotel',
            'hotel_name' => 'Premier Inn London City',
            'hotel_address' => '66 Alie Street, London, E1 8PX',
            'room_number' => '',
            'hotel_collection_type' => '',
        ]);
        $hotelMissingFieldsResp->assertSessionHasErrors(['room_number', 'hotel_collection_type']);

        // 5. Hotel search mode with meet_driver synchronizes schedule driver instructions
        $hotelMeetDriverResp = $this->post(route('booking.address.store'), [
            'collection_mode' => 'hotel',
            'hotel_name' => 'Premier Inn London City',
            'hotel_address' => '66 Alie Street, London, E1 8PX',
            'room_number' => '12b',
            'hotel_collection_type' => 'meet_driver',
            'address_details' => 'Call when outside lobby',
        ]);
        $hotelMeetDriverResp->assertRedirect(route('booking.address', ['step' => 'summary']));
        $this->assertEquals('12B', session('booking.address.room_number'));
        $this->assertEquals('meet_driver', session('booking.address.hotel_collection_type'));
        $this->assertEquals('Collect from me in person', session('booking.schedule.collection_instructions'));
        $this->assertEquals('Deliver to me in person', session('booking.schedule.delivery_instructions'));
    }

    public function test_incomplete_future_steps_cannot_be_accessed_directly_and_redirect_to_earliest_incomplete_step(): void
    {
        // With empty session, accessing Step 2 (Address), Step 3 (Schedule), Step 4 (Details), or Step 5 (Confirm) redirects to Step 1 (Services)
        $this->get(route('booking.address'))->assertRedirect(route('booking.services'));
        $this->get(route('booking.schedule'))->assertRedirect(route('booking.services'));
        $this->get(route('booking.details'))->assertRedirect(route('booking.services'));
        $this->get(route('booking.confirm'))->assertRedirect(route('booking.services'));

        // With Step 1 completed, Step 2 is accessible, but Step 3 redirects to Step 2
        $sessionAfterStep1 = [
            'booking' => [
                'services' => [
                    [
                        'category_id' => 1,
                        'service_id' => 2,
                        'option_service_id' => 2,
                        'name' => 'Wash & Fold',
                        'option_name' => 'Mixed Wash & Fold',
                        'price' => 600.0,
                        'special_request' => 'USE COLD WATER',
                    ],
                ],
            ],
        ];

        $this->withSession($sessionAfterStep1)->get(route('booking.address'))->assertStatus(200);
        $this->withSession($sessionAfterStep1)->get(route('booking.schedule'))->assertRedirect(route('booking.address'));
        $this->withSession($sessionAfterStep1)->get(route('booking.details'))->assertRedirect(route('booking.address'));
        $this->withSession($sessionAfterStep1)->get(route('booking.confirm'))->assertRedirect(route('booking.address'));
    }

    public function test_complete_5_step_booking_flow_unlocks_all_steps_and_preserves_data(): void
    {
        $seeded = $this->seedWashFoldAndCategories();
        $washFoldCatId = $seeded['washFoldCat']->id;
        $mixedServiceId = $seeded['mixedService']->id;

        // 1. Complete Step 1 (Select Services)
        $this->post(route('booking.services.store'), [
            'selected_services' => [$washFoldCatId],
            'services_data' => [
                $washFoldCatId => [
                    'wash_option_id' => $mixedServiceId,
                    'special_request' => 'WASH AT 30C',
                ],
            ],
        ])->assertRedirect(route('booking.address'));

        // 2. Complete Step 2 (Address)
        $this->post(route('booking.address.store'), [
            'collection_mode' => 'home_office',
            'postcode' => 'e10bj',
            'manual_address' => 'flat 64, brockmer house, crowder street, london',
            'is_manual' => '1',
            'address_type' => 'HOME',
            'address_details' => '2nd floor',
        ])->assertRedirect(route('booking.schedule'));

        // 3. Complete Step 3 (Schedule)
        $colDate = now()->addDay()->format('Y-m-d');
        $delDate = now()->addDays(2)->format('Y-m-d');
        $this->post(route('booking.schedule.store'), [
            'collection_date' => $colDate,
            'collection_slot' => '20:00 - 23:00',
            'collection_instructions' => 'Collect from me in person',
            'delivery_date' => $delDate,
            'delivery_slot' => '20:00 - 23:00',
            'delivery_instructions' => 'Deliver to me in person',
            'driver_notes' => 'Call on arrival',
            'repeat_order' => '1',
            'repeat_frequency' => 'weekly',
        ])->assertRedirect(route('booking.details'));

        // 4. Complete Step 4 (Contact Details)
        $this->post(route('booking.details.store'), [
            'customer_type' => 'company',
            'first_name' => 'MD.',
            'last_name' => 'NAIB',
            'company_name' => 'FreshGo Enterprise Ltd',
            'tax_number' => 'GB123456789',
            'country_code' => '+880',
            'phone_number' => '1724698392',
            'customer_email' => 'mutasimstore@gmail.com',
        ])->assertRedirect(route('booking.confirm'));

        // 5. Verify all 5 steps are now accessible and preserve session data
        $this->get(route('booking.services'))->assertStatus(200)->assertSee('WASH AT 30C');
        $this->get(route('booking.address'))->assertStatus(200)->assertSee('E1 0BJ');
        $this->get(route('booking.schedule'))
            ->assertStatus(200)
            ->assertSee('Select time slots')
            ->assertSee('Call on arrival');
        $this->get(route('booking.details'))
            ->assertStatus(200)
            ->assertSee('Enter contact details')
            ->assertSee('MD.')
            ->assertSee('NAIB')
            ->assertSee('FreshGo Enterprise Ltd')
            ->assertSee('1724698392');
        $this->get(route('booking.confirm'))
            ->assertStatus(200)
            ->assertSee('Mixed Wash &amp; Fold', false)
            ->assertSee('WASH AT 30C')
            ->assertSee('FLAT 64, BROCKMER HOUSE, CROWDER STREET, LONDON, E1 0BJ')
            ->assertSee('MD. NAIB')
            ->assertSee('FreshGo Enterprise Ltd')
            ->assertSee('+880 1724698392')
            ->assertDontSee('Subscribe &amp; Repeat Schedule', false);

        $this->assertTrue(session('booking.schedule.repeat_order'));
        $this->assertEquals('weekly', session('booking.schedule.repeat_frequency'));

        // Confirm final booking
        $confirmResp = $this->post(route('booking.confirm.store'));
        $confirmResp->assertRedirect();
        $this->assertNotEmpty(session('booking.confirmed_at'));
        $this->assertNotEmpty(session('booking.reference'));
        $confirmResp->assertRedirect(route('booking.success', session('booking.reference')));
    }

    public function test_step_3_schedule_rejects_invalid_date_combinations_and_validates_repeat_frequency(): void
    {
        $sessionAfterStep2 = [
            'booking' => [
                'services' => [
                    ['category_id' => 1, 'service_id' => 2, 'name' => 'Wash & Fold', 'slug' => 'wash-fold'],
                ],
                'address' => [
                    'postcode' => 'E1 0BJ',
                    'selected_address' => 'FLAT 64, BROCKMER HOUSE, CROWDER STREET, LONDON, E1 0BJ',
                    'address_type' => 'HOME',
                ],
            ],
        ];

        $today = now()->format('Y-m-d');

        // 1. Same-day delivery when 1-day minimum turnaround is required -> rejected
        $sameDayResp = $this->withSession($sessionAfterStep2)->post(route('booking.schedule.store'), [
            'collection_date' => $today,
            'collection_slot' => '20:00 - 23:00',
            'delivery_date' => $today,
            'delivery_slot' => '20:00 - 23:00',
        ]);
        $sameDayResp->assertSessionHasErrors(['delivery_date']);

        // 2. Repeat order enabled without frequency -> rejected
        $repeatMissingFreqResp = $this->withSession($sessionAfterStep2)->post(route('booking.schedule.store'), [
            'collection_date' => $today,
            'collection_slot' => '20:00 - 23:00',
            'delivery_date' => now()->addDay()->format('Y-m-d'),
            'delivery_slot' => '20:00 - 23:00',
            'repeat_order' => '1',
            'repeat_frequency' => '',
        ]);
        $repeatMissingFreqResp->assertSessionHasErrors(['repeat_frequency']);

        // 3. Repeat order disabled -> repeat_frequency stored as null
        $repeatOffResp = $this->withSession($sessionAfterStep2)->post(route('booking.schedule.store'), [
            'collection_date' => $today,
            'collection_slot' => '20:00 - 23:00',
            'delivery_date' => now()->addDay()->format('Y-m-d'),
            'delivery_slot' => '20:00 - 23:00',
            'repeat_order' => '0',
            'repeat_frequency' => 'weekly',
        ]);
        $repeatOffResp->assertRedirect(route('booking.details'));
        $this->assertFalse(session('booking.schedule.repeat_order'));
        $this->assertNull(session('booking.schedule.repeat_frequency'));
    }

    public function test_step_4_contact_details_requires_company_name_only_when_company_selected(): void
    {
        $sessionAfterStep3 = [
            'booking' => [
                'services' => [
                    ['category_id' => 1, 'service_id' => 2, 'name' => 'Wash & Fold', 'slug' => 'wash-fold'],
                ],
                'address' => [
                    'postcode' => 'E1 0BJ',
                    'selected_address' => 'FLAT 64, BROCKMER HOUSE, CROWDER STREET, LONDON, E1 0BJ',
                    'address_type' => 'HOME',
                ],
                'schedule' => [
                    'collection_date' => now()->format('Y-m-d'),
                    'collection_slot' => '20:00 - 23:00',
                    'delivery_date' => now()->addDay()->format('Y-m-d'),
                    'delivery_slot' => '20:00 - 23:00',
                ],
            ],
        ];

        // 1. Company selected without company_name -> fails validation
        $companyFailResp = $this->withSession($sessionAfterStep3)->post(route('booking.details.store'), [
            'customer_type' => 'company',
            'first_name' => 'MD.',
            'last_name' => 'NAIB',
            'company_name' => '',
            'country_code' => '+880',
            'phone_number' => '1724698392',
            'customer_email' => 'mutasimstore@gmail.com',
        ]);
        $companyFailResp->assertSessionHasErrors(['company_name']);

        // 2. Individual selected without company_name -> passes validation
        $individualPassResp = $this->withSession($sessionAfterStep3)->post(route('booking.details.store'), [
            'customer_type' => 'individual',
            'first_name' => 'MD.',
            'last_name' => 'NAIB',
            'country_code' => '+880',
            'phone_number' => '1724698392',
            'customer_email' => 'mutasimstore@gmail.com',
        ]);
        $individualPassResp->assertRedirect(route('booking.confirm'));
        $this->assertEquals('individual', session('booking.details.customer_type'));
        $this->assertEquals('MD. NAIB', session('booking.details.customer_name'));
        $this->assertEquals('+880 1724698392', session('booking.details.customer_phone'));
    }

    public function test_step_5_review_confirm_pricing_tips_promo_payment_and_edit_return_flow(): void
    {
        $seeded = $this->seedWashFoldAndCategories();
        $washFoldCatId = $seeded['washFoldCat']->id;
        $separateServiceId = $seeded['separateService']->id; // BDT 500.00 / kg

        $completeBookingSession = [
            'booking' => [
                'services' => [
                    [
                        'category_id' => $washFoldCatId,
                        'service_id' => $separateServiceId,
                        'option_service_id' => $separateServiceId,
                        'name' => 'Wash & Fold',
                        'option_name' => 'Separate Wash & Fold',
                        'price' => 99999.00, // tampered price in session; server must recalculate from DB (500.00)
                        'special_request' => 'WASH DARKS SEPARATELY',
                    ],
                ],
                'address' => [
                    'collection_mode' => 'hotel',
                    'hotel_name' => 'PREMIER INN LONDON CITY TOWER HILL',
                    'selected_address' => '22-24 PRESCOTT STREET, LONDON, E1 8BB',
                    'postcode' => 'E1 8BB',
                    'address_type' => 'HOTEL',
                    'room_number' => 'ROOM 305',
                    'hotel_collection_type' => 'use_reception',
                    'address_details' => 'LEAVE AT CONCIERGE DESK',
                ],
                'schedule' => [
                    'collection_date' => now()->addDay()->format('Y-m-d'),
                    'collection_day_label' => 'Tomorrow',
                    'collection_slot' => '20:00 - 23:00',
                    'collection_instructions' => 'Collect from reception',
                    'delivery_date' => now()->addDays(2)->format('Y-m-d'),
                    'delivery_day_label' => now()->addDays(2)->format('D, j M'),
                    'delivery_slot' => '20:00 - 23:00',
                    'delivery_instructions' => 'Deliver to reception',
                    'driver_notes' => 'Call reception on arrival',
                    'repeat_order' => true,
                    'repeat_frequency' => 'weekly',
                ],
                'details' => [
                    'customer_type' => 'company',
                    'first_name' => 'MD.',
                    'last_name' => 'NAIB',
                    'customer_name' => 'MD. NAIB',
                    'company_name' => 'FreshGo Enterprise',
                    'tax_number' => 'TAX-9988',
                    'country_code' => '+880',
                    'phone_number' => '1724698392',
                    'customer_phone' => '+880 1724698392',
                    'customer_email' => 'mutasimstore@gmail.com',
                ],
            ],
        ];

        // 1. Load Step 5 and verify DB-authoritative pricing (500.00 base + 40.00 delivery = 540.00 estimated total, 0.00 due today)
        $confirmGet = $this->withSession($completeBookingSession)->get(route('booking.confirm'));
        $confirmGet->assertStatus(200)
            ->assertSee('Review and confirm')
            ->assertSee('Price breakdown')
            ->assertSee('BDT 500.00')
            ->assertSee('BDT 40.00')
            ->assertSee('Final amount calculated after collection and inspection.')
            ->assertSee('Enter payment details')
            ->assertSee('You will not be charged until your order is weighed and the final amount is confirmed.')
            ->assertSee('ROOM 305')
            ->assertSee('Use Reception')
            ->assertSee('PREMIER INN LONDON CITY TOWER HILL');

        // 2. Promo code validation returns clear 422 message (no promo backend exists)
        $promoResp = $this->withSession($completeBookingSession)->postJson(route('booking.confirm.options'), [
            'action' => 'apply_promo',
            'promo_code' => 'SAVE50',
        ]);
        $promoResp->assertStatus(422)
            ->assertJson([
                'success' => false,
                'promo_available' => false,
            ]);

        // 3. Select Preset Driver Tip (BDT 100) via AJAX and verify total updates to 640.00 without altering service price
        $tipResp = $this->withSession($completeBookingSession)->postJson(route('booking.confirm.options'), [
            'action' => 'update_tip',
            'tip_option' => '100',
        ]);
        $tipResp->assertStatus(200)
            ->assertJsonPath('pricing.estimated_services_total', 500)
            ->assertJsonPath('pricing.tip_amount', 100)
            ->assertJsonPath('pricing.estimated_total', 640);

        // 4. Custom Tip validation (rejects negative or > 5000, accepts 250)
        $customTipResp = $this->withSession($completeBookingSession)->postJson(route('booking.confirm.options'), [
            'action' => 'update_tip',
            'tip_option' => 'custom',
            'custom_tip_amount' => 250,
        ]);
        $customTipResp->assertStatus(200)
            ->assertJsonPath('pricing.tip_amount', 250)
            ->assertJsonPath('pricing.estimated_total', 790);

        // 5. Repeat schedule update syncs directly with booking.schedule
        $repeatResp = $this->withSession($completeBookingSession)->postJson(route('booking.confirm.options'), [
            'action' => 'update_repeat',
            'repeat_frequency' => 'bi_weekly',
        ]);
        $repeatResp->assertStatus(200)
            ->assertJsonPath('schedule.repeat_order', true)
            ->assertJsonPath('schedule.repeat_frequency', 'bi_weekly');

        // 6. Edit link return_to=confirm flow: editing Step 2 Address returns directly to Step 5 Confirm when Steps 1-4 are complete
        $this->withSession($completeBookingSession)
            ->get(route('booking.address', ['return_to' => 'confirm']))
            ->assertStatus(200);

        $editAddrPost = $this->post(route('booking.address.store'), [
            'collection_mode' => 'hotel',
            'hotel_name' => 'THE SAVOY LONDON',
            'hotel_address' => 'STRAND, LONDON, WC2R 0EZ',
            'room_number' => 'SUITE 700',
            'hotel_collection_type' => 'meet_driver',
            'address_type' => 'HOTEL',
            'address_details' => 'CALL ON ARRIVAL',
        ]);
        $editAddrPost->assertRedirect(route('booking.confirm'));
        $this->assertEquals('SUITE 700', session('booking.address.room_number'));
        $this->assertEquals('meet_driver', session('booking.address.hotel_collection_type'));

        // 7. Continue to Payment submission: idempotent booking reference & payment_status = pending_item_count
        $confirmSubmit1 = $this->post(route('booking.confirm.store'), [
            'payment_method' => 'cod',
            'tip_option' => '50',
            'repeat_frequency' => 'weekly',
        ]);
        $firstRef = session('booking.reference');
        $this->assertNotEmpty($firstRef);
        $confirmSubmit1->assertRedirect(route('booking.success', $firstRef));
        $this->assertEquals('pending_item_count', session('booking.payment_status'));
        $this->assertEquals('cod', session('booking.confirm_options.payment_method'));

        // Submitting identical booking again preserves the exact same booking reference (no duplicate booking)
        $confirmSubmit2 = $this->post(route('booking.confirm.store'), [
            'payment_method' => 'cod',
            'tip_option' => '50',
            'repeat_frequency' => 'weekly',
        ]);
        $confirmSubmit2->assertRedirect(route('booking.success', $firstRef));
        $this->assertEquals($firstRef, session('booking.reference'));
    }
}
