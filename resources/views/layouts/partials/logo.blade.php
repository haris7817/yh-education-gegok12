 <div class="w-full lg:w-1/3 mx-auto mb-2 flex justify-center">
        <a href="{{ url('/') }}" >
            {{-- School logo from the configurable sitelogo setting (Settings → General); falls back to the bundled YH Education logo. --}}
            <img src="{{ asset(\Config::get('settings.sitelogo') ?: 'images/yh-education-logo.png') }}" class="inline-block mx-auto" style="height:80px;" alt="YH Education">
        </a>
</div>
