{{-- HERO SECTION --}}
<section class="relative overflow-hidden">
    <!-- Main White Box -->
    <div class="relative container bg-white py-5 flex flex-col gap-12 lg:gap-20 ">

        <!-- Hero Content Grid -->
        <div class="flex-1 grid xl:grid-cols-2 gap-16 xl:gap-8 items-center pb-6">

            <!-- Left Column: Text -->
            <div class="max-w-2xl mx-auto xl:mx-0 md:pt-10">
                <h1 class="text-5xl md:text-6xl uppercase text-black font-bold leading-[1.05] tracking-tight mb-6">
                    Your <span class="text-brand">Campaigns,</span><br><span class="text-brand">Delivered</span>
                    Instantly.
                </h1>
                <p class="text-lg md:text-xl text-gray-600 leading-relaxed mb-10">
                    With Arzonet, master the art of email delivery as we harness smart analytics, dynamic routing, and
                    intelligent retries to transform your bulk sending strategy.
                </p>
                <div class="flex flex-wrap items-center gap-6 mb-10">
                    <form action="{{ route('auth.start') }}" method="POST" class="w-full md:w-xl">
                        @csrf
                        <div class="border-2 rounded-full flex items-center relative w-full">
                            <input class="form-control p-4 pr-28 w-full rounded-full ring-0 outline-0" type="email" name="email" required
                                placeholder="Email Address">
                            <button type="submit" class="absolute right-1 rounded-full text-white px-6 py-3 bg-surface-800 ">Get
                                Started</button>
                        </div>
                    </form>
                </div>


                <!-- Testimonials -->
                <div class="flex flex-wrap items-center gap-6 mt-10">
                    <div class="icons flex items-center -space-x-4">
                        <img src="https://ui-avatars.com/api/?name=J+D&background=ff6b4a&color=fff"
                            class="w-10 h-10 rounded-full border-2 border-white">
                        <img src="https://ui-avatars.com/api/?name=A+S&background=000&color=fff"
                            class="w-10 h-10 rounded-full border-2 border-white">
                        <img src="https://ui-avatars.com/api/?name=R+B&background=ff6b4a&color=fff"
                            class="w-10 h-10 rounded-full border-2 border-white">
                    </div>
                    <div>
                        <div class="stars text-yellow-400 text-lg">
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star-half"></i>
                        </div>
                        <div class="text-gray-600 text-sm">43k+ happy users</div>
                    </div>

                </div>
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 max-w-2xl gap-4 mt-10 md:pl-1">
                    <div class="flex flex-col items-center md:items-start">
                        <div class="text-4xl font-black text-black mb-2">100%</div>
                        <div class="text-gray-600 text-sm">Server Uptime</div>
                    </div>
                    <div class="flex flex-col items-center md:items-start">
                        <div class="text-4xl font-black text-black mb-2">43K+</div>
                        <div class="text-gray-600 text-sm">Active Users</div>
                    </div>
                    <div class="flex flex-col items-center md:items-start">
                        <div class="text-4xl font-black text-black mb-2">5.7M+</div>
                        <div class="text-gray-600 text-sm">Email/Month</div>
                    </div>
                    <div class="flex flex-col items-center md:items-start">
                        <div class="text-4xl font-black text-black mb-2">24/7</div>
                        <div class="text-gray-600 text-sm">Live Support</div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Bento Box -->
            <div
                class="relative grid grid-cols-12 grid-rows-12 gap-5 h-[500px] md:h-[580px] pt-10 w-full max-w-[700px] mx-auto xl:ml-auto select-none mt-10 xl:mt-0">

                <!-- Top Left Orange Box -->
                <div
                    class="col-span-5 row-span-5 bg-brand rounded-md p-4 text-white flex flex-col relative overflow-hidden shadow-sm">
                    <div class="flex -space-x-3 mb-auto">
                        <img src="https://ui-avatars.com/api/?name=J+D&background=000&color=fff"
                            class="w-10 h-10 rounded-full border-2 border-brand">
                        <img src="https://ui-avatars.com/api/?name=A+S&background=1a1f36&color=fff"
                            class="w-10 h-10 rounded-full border-2 border-brand">
                        <img src="https://ui-avatars.com/api/?name=R+B&background=fff&color=000"
                            class="w-10 h-10 rounded-full border-2 border-brand">
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl md:text-5xl font-black mb-2 font-['Outfit']">87.4M+</h3>
                        <p class="text-xs md:text-sm text-white/90 leading-relaxed font-medium">Emails Delivered <br>
                            Successfully</p>
                    </div>
                </div>

                <!-- Top Right Image -->
                <div class="col-span-7 row-span-8 rounded-md overflow-hidden h-full shadow-sm relative">
                    <!-- Faint overlay to match the tone -->
                    <div class="absolute inset-0 bg-[#1a1f36]/5 mix-blend-multiply z-10"></div>
                    <img src="{{ asset('images/landing/hero.jpg') }}" class="w-full h-full object-cover rounded-md"
                        alt="Woman holding tablet">
                </div>

                <!-- Bottom Left Orange Box -->
                <div
                    class="col-span-5 row-span-3 bg-brand rounded-md p-5 md:p-6 text-white flex flex-col justify-end relative overflow-hidden shadow-sm">
                    <p class="text-sm md:text-base font-medium z-10 relative leading-tight">Successful<br>growth to our
                        users</p>
                    <svg class="absolute bottom-0 right-0 w-3/4 h-2/3 text-white/20" viewBox="0 0 100 50"
                        preserveAspectRatio="none">
                        <path d="M0,50 L20,35 L40,45 L70,10 L100,20 L100,50 Z" fill="currentColor" />
                    </svg>
                </div>


                <!-- Bottom Right Image -->
                <div class="col-span-12 row-span-4 rounded-md overflow-hidden h-full shadow-sm relative">
                    <!-- Faint overlay -->
                    <div class="absolute inset-0 bg-[#1a1f36]/10 mix-blend-multiply z-10"></div>
                    <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&q=80"
                        class="w-full h-full object-cover rounded-md" alt="Team working">
                </div>

                <!-- Circular Play Button -->
                <div
                    class="absolute left-[calc(41.666%-2.5rem)] top-[58%] -translate-y-1/2 w-20 h-20 md:w-24 md:h-24 bg-[#1a1f36] rounded-full flex items-center justify-center text-white border-[6px] border-white z-30 cursor-pointer hover:scale-105 transition-transform group">
                    <svg class="w-6 h-6 md:w-8 md:h-8 ml-1 text-brand group-hover:scale-110 transition-transform"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                    <!-- Spinning Circular Text -->
                    <svg viewBox="0 0 100 100"
                        class="absolute inset-0 w-full h-full animate-[spin_10s_linear_infinite] pointer-events-none opacity-50">
                        <path id="curve" d="M 50 50 m -35 0 a 35 35 0 1 1 70 0 a 35 35 0 1 1 -70 0"
                            fill="transparent" />
                        <text class="text-[8px] uppercase font-bold tracking-[0.25em]" fill="currentColor">
                            <textPath href="#curve" startOffset="0%">WATCH VIDEO OF OUR ACTION • WATCH VIDEO OF OUR
                                ACTION • </textPath>
                        </text>
                    </svg>
                </div>
            </div>

        </div>
    </div>
</section>