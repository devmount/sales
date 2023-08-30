<x-filament-panels::page>
{{ __('Download starts automatically...' )}}

<script>
// human readable date, e.g. '2. Januar 2022'
const hdate = (d: Date, locale: string = 'de-DE'): string => {
    return d.toLocaleDateString(locale, { year: 'numeric', month: 'long', day: 'numeric' });
};

// short iso date for invoice number, e.g. '20220102'
const isodate = (d: Date): string => {
	return d.toISOString().replace(/-/g, '').slice(0, 8);
};

// converts a decimal into a localized two digit
const nDigit = (n: number, d: number, locale: string = 'de-DE'): string => {
	return n.toLocaleString(
		locale, {
			minimumFractionDigits: d,
			maximumFractionDigits: d
		}
	);
};

// euro number formatting
const euro = (n: number, locale: string = 'de-DE'): string => {
	return n ? nDigit(n, 2, locale) + ' €' : '-,-- €';
};

// percent number formatting
const percent = (n: number, locale: string = 'de-DE'): string => {
	return nDigit(n*100, 2, locale) + ' %';
};

document.addEventListener('DOMContentLoaded', () => {
    // document configuration
    const colors = {
        main:   '#002033',
        accent: '#3c88b8',
        text:   '#c5d6e0',
        gray:   '#5c666d',
        dark:   '#222222',
        light:  '#ffffff',
        line:   '#eeeeee',
        line2:  '#265d7f',
        line3:  '#66808e',
        col1:   '#cccccc',
        col2:   '#dddddd',
        col3:   '#eeeeee',
        col4:   '#bbbbbb'
    };
    const config = {
        accountHolder: 'Config.AccountHolder',
        address: 'Config.Address',
        bank: 'Config.Bank',
        bic: 'Config.Bic',
        email: 'Config.Email',
        iban: 'Config.Iban',
        phone: 'Config.Phone',
        taxOffice: 'Config.TaxOffice',
        vatId: 'Config.VatId',
        website: 'Config.Website',
    };
    const positionRowHeight = 3.5;
    let page = 1;
    const totalPageCount = 1; // TODO
    const today = new Date();
    const invoiceNumber = isodate(new Date()) + '{{ $this->record->id }}'.padStart(4, '0');
    const billedPerProject = {{ $this->record->pricing_unit === 'p' ? 'true' : 'false' }};
    const discount = {{ (int)$this->record->discount }};

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // doc.text('{{ $this->record->title }}', 10, 10);

    // fonts
    doc.addFont('fonts/FiraSans-Regular.ttf', 'FiraSansRegular', 'normal');
    doc.addFont('fonts/FiraSans-ExtraLight.ttf', 'FiraSansExtraLight', 'normal');
    doc.addFont('fonts/FiraSans-ExtraBold.ttf', 'FiraSansExtraBold', 'normal');
    // document guides
    // doc.line(0, 105, 3, 105).line(0, 148, 5, 148).line(0, 210, 3, 210)
    // page header
    doc.setFillColor(colors.main).rect(0, 9, 210, 30, 'F');
    // doc.addImage(logo, 'JPEG', 12, 13, 22, 22);
    doc.setTextColor(colors.light).setFont('FiraSansExtraLight')
        .setFontSize(26).text('{{ __("Invoice") }}'.toUpperCase(), 105, 27, { align: 'center' })
        .setFontSize(9)
            .text(config.phone, 195, 19, { align: 'right' })
            .text(config.email, 195, 25, { align: 'right' })
            .text(config.website, 195, 31, { align: 'right' });
    // doc.addImage(iconPhone, 'JPEG', 198, 16, 4, 4);
    // doc.addImage(iconSend, 'JPEG', 198, 22, 4, 4);
    // doc.addImage(iconLink, 'JPEG', 198, 28, 4, 4);
    // address header
    doc.setTextColor(colors.gray).setFont('FiraSansExtraLight').setFontSize(8)
        .text(config.address, 10, 50)
        .setFontSize(9).text(this.l.to, 10, 62)
        .setTextColor(colors.main).setFontSize(15).text('{{ $this->record->project->client->name }}'.toUpperCase(), 10, 69)
        .setDrawColor(colors.line).setLineWidth(0.4).line(0, 73, 70, 73).line(140, 73, 210, 73)
        .setTextColor(colors.gray)
            .setFontSize(10)
                .setLineHeightFactor(1.5).text('{{ $this->record->project->client->address }}', 10, 79)
                .text('{{ __("Invoice Number") }}', 144, 62.8)
                .text('{{ __("Invoice Date") }}', 144, 68.8)
            .setFont('FiraSansRegular').setTextColor(colors.main)
                .text(invoiceNumber, 202, 62.8, { align: 'right' })
                .text(hdate(today, '{{ $this->record->project->client->language }}'), 202, 68.8, { align: 'right' });
    // invoice table content
    doc.setLineWidth(0.8)
        .setFillColor(colors.col3).rect(10, 105, 90, 56, 'F').setDrawColor(colors.col2).line(10, 133, 100, 133)
        .setFillColor(colors.col2).rect(100, 105, 31, 56, 'F').setDrawColor(colors.col1).line(100, 133, 131, 133)
        .setFillColor(colors.col1).rect(131, 105, 30, 56, 'F').setDrawColor(colors.col4).line(131, 133, 162, 133)
        .setFillColor(colors.accent).rect(162, 105, 40, 56, 'F').setDrawColor(colors.line2).line(162, 133, 202, 133)
        .setFontSize(13)
            .setFont('FiraSansExtraLight').setTextColor(colors.dark)
                .text('{{ __("Description") }}', 15, 118)
                .text('{{ __("Quantity") }}', 115, 118, { align: 'center' })
                .text('{{ __("Price") }}', 146, 118, { align: 'center' })
            .setFont('FiraSansRegular').setTextColor(colors.light)
                .text('{{ __("Total") }}', 182, 118, { align: 'center' })
        .setFontSize(8)
            .setFont('FiraSansExtraLight').setTextColor(colors.dark)
                .text('{{ __("Statement of Work") }}', 15, 124)
                .text(billedPerProject ? '{{ __("flat-rate") }}' : '{{ __("In hours") }}', 115, 124, { align: 'center' })
                .text(billedPerProject ? '{{ __("flat-rate") }}' : '{{ __("Per hour") }}', 146, 124, { align: 'center' })
            .setTextColor(colors.light)
                .text(billedPerProject ? '{{ __("Sum") }}' : '{{ __("Sum of all positions") }}', 182, 124, { align: 'center' })
        .setTextColor(colors.dark).setFont('FiraSansRegular').setFontSize(9).text('{{ $this->record->title }}', 15, 141)
        .setFont('FiraSansExtraLight')
            .setFontSize(8)
                .text('{{ $this->record->description }}', 15, 147)
            .setFontSize(16)
                .text(nDigit(billedPerProject ? 1 : {{ $this->record->hours }}, 1, '{{ $this->record->project->client->language }}'), 115, 148, { align: 'center' })
                .text(euro({{ $this->record->price }}, '{{ $this->record->project->client->language }}'), 146, 148, { align: 'center' })
            .setTextColor(colors.light)
                .text(euro({{ $this->record->net }}, '{{ $this->record->project->client->language }}'), 182, 148, { align: 'center' });
    // invoice table total without discount
    if (!discount) {
        doc.setFillColor(colors.main).rect(0, 165, 210, 50, 'F').setDrawColor(colors.line3).setLineWidth(0.3).line(124, 196, 194, 196)
            .setTextColor(colors.text)
                .setFont('FiraSansExtraLight').setFontSize(13)
                    .text('{{ __("Amount (net)") }}', 160, 181, { align: 'right' })
                    .text(percent('{{ $this->record->vat_rate }}', '{{ $this->record->project->client->language }}') + ' {{ __("Vat") }}', 160, 190, { align: 'right' })
                    .text(euro({{ $this->record->net }}, '{{ $this->record->project->client->language }}'), 194, 181, { align: 'right' })
                    .text(euro({{ $this->record->vat }}, '{{ $this->record->project->client->language }}'), 194, 190, { align: 'right' })
            .setTextColor(colors.light)
                .setFont('FiraSansRegular').setFontSize(16)
                    .text('{{ __("Total amount") }}', 160, 205, { align: 'right' })
                    .text(euro({{ $this->record->gross }}, '{{ $this->record->project->client->language }}'), 194, 205, { align: 'right' });
    }
    // invoice table total with discount
    else {
        doc.setFillColor(colors.main).rect(0, 165, 210, 50, 'F').setDrawColor(colors.line3).setLineWidth(0.3).line(124, 198, 194, 198)
            .setTextColor(colors.text)
                .setFont('FiraSansExtraLight').setFontSize(13)
                    .text('{{ __("Amount (net)") }}', 160, 177, { align: 'right' })
                    .text(this.l.credit, 160, 185, { align: 'right' })
                    .text(percent('{{ $this->record->vat_rate }}', '{{ $this->record->project->client->language }}') + ' {{ __("Vat") }}', 160, 193, { align: 'right' })
                    .text(euro({{ $this->record->net }}, '{{ $this->record->project->client->language }}'), 194, 177, { align: 'right' })
                    .text('–' + euro(discount, '{{ $this->record->project->client->language }}'), 194, 185, { align: 'right' })
                    .text(euro({{ $this->record->vat }}, '{{ $this->record->project->client->language }}'), 194, 193, { align: 'right' })
            .setTextColor(colors.light)
                .setFont('FiraSansRegular').setFontSize(16)
                    .text('{{ __("Total amount") }}', 160, 207, { align: 'right' })
                    .text(euro({{ $this->record->gross }}, '{{ $this->record->project->client->language }}'), 194, 207, { align: 'right' });
    }
    // terms
    doc.setFontSize(10).setFont('FiraSansExtraLight').setTextColor(colors.dark)
        .text(markerReplace(
            '{{ __("Thank you for your cooperation and trust. The total amount of {0} is to be transferred to the bank account mentioned below within 14 days from receipt of this invoice, quoting the invoice number {1}. Please do not hesitate to contact me if you have any questions.") }}',
            [euro({{ $this->record->gross }}, '{{ $this->record->project->client->language }}'), invoiceNumber]
        ), 10, 225, { maxWidth: 180 })
        .text(['{{ __("With kind regards") }}', 'Andreas Müller'], 10, 244);
    // footer
    doc.setDrawColor(colors.line).setLineWidth(0.4).line(10, 272, 202, 272)
        // .addImage(signature, 'PNG', 13, 255, 24, 18)
        .setLineHeightFactor(1.3).setFontSize(9).setTextColor(colors.gray)
            .text('{{ __("PAge") }} ' + page + '/' + totalPageCount, 202, 290, { align: 'right' })
            .text('Berlin, ' + hdate(today, '{{ $this->record->project->client->language }}'), 10, 277)
            .text(['{{ __("IBAN") }}', '{{ __("BIC") }}', '{{ __("Credit Institution") }}', '{{ __("Holder") }}'], 90, 277, { align: 'right' })
            .text(['{{ __("VAT Id") }}', '{{ __("Tax office") }}'], 170, 277, { align: 'right' })
        .setFont('FiraSansRegular')
            .text([config.iban, config.bic, config.bank, config.accountHolder], 92, 277)
            .text([config.vatId, config.taxOffice], 172, 277);
    page++;
    // add position pages for activity confirmation
    // this.paginatedPositions.forEach(positions => {
    //     doc = this.addPositionPages(doc, positions);
    // });
    // serve document
    doc.save(
        invoiceNumber + '_{{ __("Invoice") }}_devmount.pdf'.toLowerCase(),
        { returnPromise: true }
    ).then(() => {
        setTimeout(() => { window.close() }, 100);
    });
});
</script>
</x-filament-panels::page>
