<x-filament-panels::page>
{{ __('Download starts automatically...' )}}

<script>
// import { Canvg } from 'https://cdn.skypack.dev/canvg@^4.0.0';
// human readable date, e.g. '2. Januar 2022'
const hdate = (d, locale = 'de-DE') => {
    return d.toLocaleDateString(locale, { year: 'numeric', month: 'long', day: 'numeric' });
};

// short iso date for invoice number, e.g. '20220102'
const isodate = (d) => {
    return d.toISOString().replace(/-/g, '').slice(0, 8);
};

// converts a decimal into a localized two digit
const nDigit = (n, d, locale = 'de-DE') => {
    return n.toLocaleString(
        locale, {
            minimumFractionDigits: d,
            maximumFractionDigits: d
        }
    );
};

// euro number formatting
const euro = (n, locale = 'de-DE') => {
    return n ? nDigit(n, 2, locale) + ' €' : '-,-- €';
};

// percent number formatting
const percent = (n, locale = 'de-DE') => {
    return nDigit(n*100, 2, locale) + ' %';
};

// replace all marker of format {i}
const markerReplace = (s, list) => {
    list.forEach((element, i) => {
        s = s.replace('{' + i.toString() + '}', element);
    });
    return s;
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
        name: 'Config.Name',
        phone: 'Config.Phone',
        taxOffice: 'Config.TaxOffice',
        vatId: 'Config.VatId',
        website: 'Config.Website',
        signature: '<svg xmlns="http://www.w3.org/2000/svg" width="253.09" height="191.86" viewBox="0 0 253.09 191.86"><path d="M123.52,147.86c-.45.21-.94.65-1.35.59-3.33-.5-6,1.71-9,2.13-4.33.61-5.82,1-6.72-3.78-3.84,2.24-6.37,7.17-12,5.79l-1-2.52c-1.33.74-2.54,1.44-3.78,2.09A17.55,17.55,0,0,1,87,153.45c-4.28,1.4-6.69-.65-6.09-5.11.58-4.3,3-7.74,5.15-11.33a1.07,1.07,0,0,0,.15-1l-8.14,6.1-.56-.55c2.16-2.78,4.21-5.66,6.49-8.33,4.33-5.1,8.8-10.09,13.22-15.12.34-.38.64-.91,1.07-1.06a12.47,12.47,0,0,1,2.37-.39c0,.73.29,1.64,0,2.18-1.7,2.85-3.56,5.6-5.7,8.9l-.39-2.54c-1.68,2.41-3.83,4.12-2.9,7.3.2.69-.89,1.73-1.34,2.63-2,3.94-4,7.86-5.89,11.83a13.51,13.51,0,0,0-.45,2.44c-.39,1.64.62,1.64,1.73,1.54,3.13-.28,5.48-2.24,7.85-3.95s4.11-3.84,6.32-5.56c.64-.5,1.9-.22,3.56-.34L96.33,150l.7.58c7.44-2.58,12.52-9.14,19.39-12.69l.86,1.06q-4,4.51-7.9,9l.47.64c2.83-.15,5.66-.37,7.81-2.74a53.74,53.74,0,0,1,3.68-4.12c.53-.48,1.63-.34,2.47-.48a11.47,11.47,0,0,1-.63,2.21c-.32.63-.91,1.12-1.23,1.88,2.35-1.07,4.75-1.71,6.43-4.16q5.9-8.59,12.42-16.74c7.24-9,14.64-17.86,22.1-26.66a122.23,122.23,0,0,1,8.61-8.88c5.91-5.7,11.8-11.43,17.91-16.91,7.22-6.47,14.5-12.89,22.06-19,11.08-8.89,22.46-17.43,33.66-26.17,3.72-2.9,7.28-6,11-8.94a11.44,11.44,0,0,1,2.31-1.1l-.85-1h-2c1-1.79,6.33-4.79,7.83-4.55s1.79,2.43.56,3.76A49.25,49.25,0,0,1,259,19.76q-9.09,7.48-18.32,14.83c-8.46,6.72-17,13.32-25.47,20.07-5.13,4.1-10.25,8.23-15.13,12.62-7.3,6.58-14.5,13.27-21.52,20.14-5.63,5.52-11.11,11.22-16.36,17.11-6.1,6.85-11.88,14-17.78,21-1.83,2.17-3.73,4.3-5.39,6.59-2,2.75-3.74,5.65-5.6,8.49l-7.74,11.73,5.59-.28a19.79,19.79,0,0,1-1.43,3.15q-2.61,3.71-5.41,7.29c-1.82,2.36-3.78,4.62-5.59,7s-3.72,5.07-5.59,7.59c-1.49,2-3.17,3.91-4.46,6-1.75,2.89-2.88,6.2-4.88,8.88s-1.4,6.59-4.63,8.4a15,15,0,0,1-2.87.9c-.14-1-.72-2.22-.34-3,1.94-3.74,4.14-7.35,6.24-11,1.34-2.32,2.74-4.62,4-7,3.7-7.16,7.21-14.41,11-21.5,2-3.67,4.46-7.07,6.71-10.6Zm-5.26,17.25.68.5,6.55-9.05-.58-.45C121.15,157.91,120.65,162.24,118.26,165.11Z" transform="translate(-11.6 -11.24)"/><path d="M11.6,202.57a217.33,217.33,0,0,1,14.31-26.28c4.28-6.75,8.17-13.74,12.5-20.45,2.56-4,5.73-7.53,8.47-11.38,1.76-2.47,3.16-5.19,4.9-7.68s3.69-4.73,5.47-7.15c1.31-1.78,2.43-3.72,3.75-5.49,3-4.06,6-8.2,9.21-12.06Q81.4,98.85,92.89,85.87c4.62-5.24,9.28-10.46,14.2-15.41,6.31-6.34,13-12.33,19.33-18.59,4-3.93,7.77-8.09,11.65-12.14.29-.3.61-.74,1-.79,1.05-.14,2.13-.12,3.2-.17a7.35,7.35,0,0,1-.76,2.63c-4.17,5.16-8.59,10.13-12.64,15.37-2.3,3-4,6.39-6.08,9.56-2.58,3.94-5.3,7.78-7.84,11.75-3.11,4.86-5.77,10.06-9.29,14.59-1.35,1.74-1.67,4-3.9,5.56-2.51,1.78-3.61,5.55-5.38,8.42s-3.52,5.44-5.17,8.23c-1,1.62-1.58,3.46-2.55,5.08-4.56,7.63-9.2,15.22-13.78,22.83a6.46,6.46,0,0,0-.5,1.48l5.22-2.1.45.74c-1.79,1.81-3.53,3.67-5.4,5.38a5.24,5.24,0,0,1-2.47,1.13c-2,.39-3.25-.76-2.51-2.59a73.15,73.15,0,0,1,4.87-9.89c3.42-5.82,6.86-11.65,10.71-17.19a158,158,0,0,0,10.32-16.49c1.15-2.19,3.18-3.9,4.62-6,1.13-1.6,1.88-3.46,2.92-5.13,3.58-5.72,7.22-11.4,10.82-17.1q4.35-6.9,8.65-13.82a5.78,5.78,0,0,0,.13-1.4c-3.19,3-6.06,5.56-8.81,8.26-4.3,4.23-8.59,8.48-12.75,12.85-3.79,4-7.47,8.07-11.1,12.18q-6.6,7.45-13,15c-2.17,2.58-4.11,5.36-6.19,8-1.55,2-3.24,3.84-4.76,5.83-2.4,3.11-4.83,6.22-7,9.47-3.51,5.16-6.83,10.46-10.28,15.67-1.54,2.32-3.26,4.53-4.84,6.84-2.34,3.43-4.69,6.87-6.93,10.37-2.66,4.16-5.23,8.39-7.81,12.6-1.47,2.4-2.9,4.83-4.32,7.26Q20.9,191,17,197.85a25.48,25.48,0,0,0-1.89,3.81C14.49,203.29,13.51,203.44,11.6,202.57Z" transform="translate(-11.6 -11.24)"/><path d="M130.66,101.64l-1,4.74,1.68-.93.45.75c-1,.53-2,1.4-3.07,1.5a9.05,9.05,0,0,1-3.81-.82,3,3,0,0,1,.31-2.22A54.47,54.47,0,0,1,130.66,101.64Z" transform="translate(-11.6 -11.24)"/><path d="M142.73,99.79l2.48,4.36a26.17,26.17,0,0,1-4.34,1.48c-.25.05-1.08-1.55-1.17-2.43C139.49,101.13,140.68,100,142.73,99.79Z" transform="translate(-11.6 -11.24)"/><path d="M118.26,165.11c2.39-2.87,2.89-7.2,6.65-9l.58.45-6.55,9.05Z" transform="translate(-11.6 -11.24)" style="fill:#fff"/></svg>',
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
    doc.addFont('/fonts/FiraSans-Regular.ttf', 'FiraSansRegular', 'normal');
    doc.addFont('/fonts/FiraSans-ExtraLight.ttf', 'FiraSansExtraLight', 'normal');
    doc.addFont('/fonts/FiraSans-ExtraBold.ttf', 'FiraSansExtraBold', 'normal');
    // document guides
    // doc.line(0, 105, 3, 105).line(0, 148, 5, 148).line(0, 210, 3, 210)
    // page header
    doc.setFillColor(colors.main).rect(0, 9, 210, 30, 'F');
    // doc.addImage(logo, 'JPEG', 12, 13, 22, 22);
    doc.setTextColor(colors.light).setFont('FiraSansExtraLight')
        .setFontSize(26).text('{{ __("Invoice") }}'.toUpperCase(), 105, 27, { align: 'center' })
        .setFontSize(9)
            .text(config.phone, 202, 19, { align: 'right' })
            .text(config.email, 202, 25, { align: 'right' })
            .text(config.website, 202, 31, { align: 'right' });
    // address header
    doc.setTextColor(colors.gray).setFont('FiraSansExtraLight').setFontSize(8)
        .text(config.address, 10, 50)
        .setFontSize(9).text('{{ __("To") }}', 10, 62)
        .setTextColor(colors.main).setFontSize(15).text('{{ $this->record->project->client->name }}'.toUpperCase(), 10, 69)
        .setDrawColor(colors.line).setLineWidth(0.4).line(0, 73, 70, 73).line(140, 73, 210, 73)
        .setTextColor(colors.gray)
            .setFontSize(10)
                .setLineHeightFactor(1.5).text('{{ str_replace("\n", "\\n", $this->record->project->client->address) }}', 10, 79)
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
                .text('{{ str_replace("\n", "\\n", $this->record->description) }}', 15, 147)
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
                    .text('{{ __("Credit") }}', 160, 185, { align: 'right' })
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
        .text(['{{ __("With kind regards") }}', config.name], 10, 244);
    // footer
    doc.setDrawColor(colors.line).setLineWidth(0.4).line(10, 272, 202, 272)
        // .addImage(signature, 'PNG', 13, 255, 24, 18)
        // .addSvgAsImage(config.signature, 13, 255, 24, 18)
        .setLineHeightFactor(1.3).setFontSize(9).setTextColor(colors.gray)
            .text('{{ __("Page") }} ' + page + '/' + totalPageCount, 202, 290, { align: 'right' })
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
