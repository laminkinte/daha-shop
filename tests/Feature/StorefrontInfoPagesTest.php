<?php

namespace Tests\Feature;

use App\Livewire\Storefront\AboutPage;
use App\Livewire\Storefront\ContactPage;
use App\Livewire\Storefront\FaqPage;
use App\Livewire\Storefront\PrivacyPage;
use App\Livewire\Storefront\TermsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StorefrontInfoPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_loads(): void
    {
        Livewire::test(AboutPage::class)->assertSee('About Daha Shop');
    }

    public function test_contact_page_loads(): void
    {
        Livewire::test(ContactPage::class)->assertSee('Contact Us');
    }

    public function test_faq_page_loads(): void
    {
        Livewire::test(FaqPage::class)->assertSee('Frequently Asked Questions');
    }

    public function test_terms_page_loads(): void
    {
        Livewire::test(TermsPage::class)->assertSee('Terms of Service');
    }

    public function test_privacy_page_loads(): void
    {
        Livewire::test(PrivacyPage::class)->assertSee('Privacy Policy');
    }

    public function test_home_page_footer_links_to_info_pages(): void
    {
        // A real HTTP request, not Livewire::test() - the footer lives in
        // the layout the full-page component is wrapped in, which
        // Livewire::test()'s component-only render doesn't include.
        $this->get(route('storefront.home'))
            ->assertSee('About Us')
            ->assertSee('Contact Us')
            ->assertSee('FAQ')
            ->assertSee('Terms of Service')
            ->assertSee('Privacy Policy');
    }
}
