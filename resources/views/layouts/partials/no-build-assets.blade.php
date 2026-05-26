<!-- Fonts -->
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

<!-- Assets for hosts without Node/Vite -->
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    const normalizeText = (value) =>
	String(value ?? '')
		.normalize('NFD')
		.replace(/[\u0300-\u036f]/g, '')
		.toLocaleLowerCase();

    Alpine.data('searchableSelect', ({
	name = '',
	options = [],
	selected = '',
	placeholder = '-- Chọn --',
	searchPlaceholder = 'Tìm kiếm...',
	emptyText = 'Không tìm thấy kết quả',
	dependsOn = '',
	dependencyValue = '',
	filterKey = 'supplier_id',
    } = {}) => ({
	open: false,
	query: '',
	name,
	options,
	selectedValue: String(selected ?? ''),
	placeholder,
	searchPlaceholder,
	emptyText,
	dependsOn,
	dependencyValue: String(dependencyValue ?? ''),
	filterKey,

	init() {
	    window.addEventListener('searchable-select-change', (event) => {
		if (!this.dependsOn || event.detail?.name !== this.dependsOn) {
		    return;
		}

		this.dependencyValue = String(event.detail?.value ?? '');

		if (this.selectedValue && !this.filteredOptions.some((option) => String(option.value) === String(this.selectedValue))) {
		    this.selectedValue = '';
		}
	    });
	},

	get filteredOptions() {
	    const query = normalizeText(this.query);
	    const dependencyValue = String(this.dependencyValue ?? '');

	    if (this.dependsOn && !dependencyValue) {
		return [];
	    }

	    let options = this.options;

	    if (this.dependsOn && dependencyValue) {
		options = options.filter((option) => String(option[this.filterKey] ?? '') === dependencyValue);
	    }

	    if (!query) {
		return options;
	    }

	    return options.filter((option) => normalizeText(option.label).includes(query));
	},

	get selectedOption() {
	    return this.options.find((option) => String(option.value) === String(this.selectedValue));
	},

	get selectedLabel() {
	    return this.selectedOption?.label ?? this.placeholder;
	},

	openMenu() {
	    if (this.dependsOn && !String(this.dependencyValue ?? '')) {
		return;
	    }

	    this.open = true;
	    this.query = '';

	    this.$nextTick(() => {
		this.$refs.search?.focus();
	    });
	},

	closeMenu() {
	    this.open = false;
	    this.query = '';
	},

	toggleMenu() {
	    if (this.open) {
		this.closeMenu();
		return;
	    }

	    this.openMenu();
	},

	selectOption(option) {
	    this.selectedValue = String(option.value);
	    this.closeMenu();

	    window.dispatchEvent(new CustomEvent('searchable-select-change', {
		detail: {
		    name: this.name,
		    value: this.selectedValue,
		},
	    }));
	},
    }));
});
</script>