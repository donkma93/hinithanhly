<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Cài đặt hệ thống</h2>
            <p class="text-sm text-gray-500">Thiết lập tài khoản ngân hàng, tỷ lệ chiết khấu và nội dung hiển thị ngoài trang chủ.</p>
        </div>
    </x-slot>

    @php($portalCardsForm = old('portal_cards', $portalCards))
    @php($portalCardsForm = count($portalCardsForm) > 0 ? $portalCardsForm : [['eyebrow' => '', 'title' => '', 'description' => '']])

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-10xl px-3 sm:px-6 lg:px-10">

        @if(session('status'))
            <div class="mb-4 rounded-md bg-emerald-50 p-3 text-emerald-700">{{ session('status') }}</div>
        @endif

            <form method="POST" action="{{ route('settings.payment.update') }}" class="space-y-6">
                @csrf

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">Thông tin ngân hàng</h3>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Tên ngân hàng</label>
                        <select name="bank_name" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">
                            <option value="">-- Chọn ngân hàng --</option>
                            @foreach(config('banks', []) as $code => $label)
                                <option value="{{ $code }}" @selected(old('bank_name', $bankName) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Số tài khoản</label>
                            <input name="bank_account" value="{{ old('bank_account', $accountNumber) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Tên chủ tài khoản</label>
                            <input name="bank_account_name" value="{{ old('bank_account_name', $accountName) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" />
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">Chiết khấu theo loại nhà cung cấp</h3>
                    <p class="text-sm text-gray-500">Nhập tỷ lệ phần trăm cố định cho từng loại. Giá trị này sẽ được dùng làm mặc định cho các nghiệp vụ liên quan tới nhà cung cấp.</p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach(\App\Models\Supplier::TYPES as $type => $label)
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        name="supplier_discount_{{ $type }}"
                                        value="{{ old('supplier_discount_'.$type, $supplierDiscountRates[$type] ?? 0) }}"
                                        class="w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900"
                                    >
                                    <span class="text-sm text-gray-500">%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Thông tin liên hệ ngoài trang chủ</h3>
                        <p class="text-sm text-gray-500">Những thông tin này sẽ hiển thị ở phần liên hệ của trang chủ public.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Địa chỉ cửa hàng</label>
                        <textarea name="store_address" rows="3" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">{{ old('store_address', \App\Models\Setting::get('store_address', '')) }}</textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Hotline</label>
                            <input name="store_hotline" value="{{ old('store_hotline', \App\Models\Setting::get('store_hotline', '')) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Giờ mở cửa</label>
                            <input name="store_hours" value="{{ old('store_hours', \App\Models\Setting::get('store_hours', '')) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Link bản đồ</label>
                        <input name="store_map_url" value="{{ old('store_map_url', \App\Models\Setting::get('store_map_url', '')) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="https://..." />
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200 space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Nội dung trang chủ</h3>
                        <p class="text-sm text-gray-500">Bạn có thể tự thêm nhiều khối thông tin để hiển thị bên ngoài trang chủ theo đúng ý mình.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Nhãn đầu trang</label>
                            <input name="portal_hero_badge" value="{{ old('portal_hero_badge', $portalHeroBadge) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Tiêu đề đầu trang</label>
                            <input name="portal_hero_title" value="{{ old('portal_hero_title', $portalHeroTitle) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Mô tả đầu trang</label>
                        <textarea name="portal_hero_description" rows="3" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900">{{ old('portal_hero_description', $portalHeroDescription) }}</textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Tiêu đề section</label>
                            <input name="portal_info_section_title" value="{{ old('portal_info_section_title', \App\Models\Setting::get('portal_info_section_title', 'Thông tin từ cửa hàng')) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Mô tả section</label>
                            <input name="portal_info_section_intro" value="{{ old('portal_info_section_intro', \App\Models\Setting::get('portal_info_section_intro', '')) }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" />
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                        Dùng các khối bên dưới để giới thiệu ngắn gọn chính sách, quy trình nhận hàng, thời gian đối soát hoặc bất kỳ thông tin nào bạn muốn đưa ra ngoài trang chủ.
                    </div>

                    <div id="portal-card-list" class="space-y-4">
                        @foreach ($portalCardsForm as $index => $card)
                            <div class="portal-card-item rounded-2xl border border-slate-200 p-4">
                                <div class="grid gap-4 lg:grid-cols-[180px_220px_1fr_auto] lg:items-start">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Nhãn nhỏ</label>
                                        <input name="portal_cards[{{ $index }}][eyebrow]" value="{{ $card['eyebrow'] ?? '' }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Ví dụ: THÔNG BÁO" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Tiêu đề</label>
                                        <input name="portal_cards[{{ $index }}][title]" value="{{ $card['title'] ?? '' }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Nhập tiêu đề hiển thị" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Nội dung</label>
                                        <textarea name="portal_cards[{{ $index }}][description]" rows="3" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Nhập nội dung muốn hiển thị ngoài trang chủ">{{ $card['description'] ?? '' }}</textarea>
                                    </div>
                                    <div class="flex items-end">
                                        <button type="button" class="portal-card-remove rounded-xl border border-rose-200 px-4 py-3 text-sm font-semibold text-rose-600 hover:bg-rose-50">Xóa</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div>
                        <button type="button" id="portal-card-add" class="rounded-xl border border-sky-200 px-4 py-3 text-sm font-semibold text-sky-700 hover:bg-sky-50">Thêm thông tin</button>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-xl bg-emerald-500 px-5 py-3 text-white font-semibold">Lưu cài đặt</button>
                </div>
            </form>
        </div>
    </div>

    <template id="portal-card-template">
        <div class="portal-card-item rounded-2xl border border-slate-200 p-4">
            <div class="grid gap-4 lg:grid-cols-[180px_220px_1fr_auto] lg:items-start">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nhãn nhỏ</label>
                    <input data-field="eyebrow" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Ví dụ: THÔNG BÁO" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tiêu đề</label>
                    <input data-field="title" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Nhập tiêu đề hiển thị" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nội dung</label>
                    <textarea data-field="description" rows="3" class="mt-1 w-full rounded-xl border-gray-300 focus:border-slate-900 focus:ring-slate-900" placeholder="Nhập nội dung muốn hiển thị ngoài trang chủ"></textarea>
                </div>
                <div class="flex items-end">
                    <button type="button" class="portal-card-remove rounded-xl border border-rose-200 px-4 py-3 text-sm font-semibold text-rose-600 hover:bg-rose-50">Xóa</button>
                </div>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cardList = document.getElementById('portal-card-list');
            const addButton = document.getElementById('portal-card-add');
            const template = document.getElementById('portal-card-template');

            const refreshCardNames = () => {
                cardList?.querySelectorAll('.portal-card-item').forEach((item, index) => {
                    item.querySelectorAll('[data-field], input[name^="portal_cards["], textarea[name^="portal_cards["]').forEach((field) => {
                        const key = field.getAttribute('data-field')
                            || field.name.match(/portal_cards\[\d+\]\[(.+)\]/)?.[1];

                        if (!key) {
                            return;
                        }

                        field.name = `portal_cards[${index}][${key}]`;
                    });
                });
            };

            addButton?.addEventListener('click', () => {
                if (!cardList || !template) {
                    return;
                }

                const fragment = template.content.cloneNode(true);
                cardList.appendChild(fragment);
                refreshCardNames();
            });

            cardList?.addEventListener('click', (event) => {
                const button = event.target.closest('.portal-card-remove');

                if (!button) {
                    return;
                }

                const items = cardList.querySelectorAll('.portal-card-item');
                if (items.length === 1) {
                    items[0].querySelectorAll('input, textarea').forEach((field) => {
                        field.value = '';
                    });

                    return;
                }

                button.closest('.portal-card-item')?.remove();
                refreshCardNames();
            });

            refreshCardNames();
        });
    </script>
</x-app-layout>
