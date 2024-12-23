@php
    $lang = $this->record?->project?->client?->language ?? 'de';
@endphp

<x-filament-panels::page>
{{-- {{ __('downloadStartsAutomatically' )}} --}}
<iframe id="preview" class="w-full"></iframe>

<script>
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

// decoding HTML characters
const decodeHtml = (html) => {
    const txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
};

// replace all marker of format {i}
const markerReplace = (s, list) => {
    list.forEach((element, i) => {
        s = s.replace('{' + i.toString() + '}', element);
    });
    return s;
};

const undated = {{ $record->undated ? 'true' : 'false' }};

// sort positions depending on invoice positions being flagged undated
const sortedPositions = (positions) => {
    return undated
        // if undated, sort by positions creation date
        ? positions.slice().sort(
            (a, b) => (new Date(a.created_at)).getTime() - (new Date(b.created_at)).getTime()
        )
        // if dated, sort by positions starting date
        : positions.slice().sort(
            (a, b) => (new Date(a.started_at)).getTime() - (new Date(b.started_at)).getTime()
        );
};

// get positions by page, one page has space for 50 lines (I know. Let me have my magic number.)
const paginatedPositions = (positions) => {
    const paginated = [];
    let linesProcessed = 0;
    sortedPositions(positions).forEach((p) => {
        const lineCount = p.description.trim().split('\n').length + 2;
        linesProcessed += lineCount;
        const i = Math.floor(linesProcessed/50);
        if (i in paginated) {
            paginated[i].push(p);
        } else {
            paginated[i] = [p];
        };
    });
    return paginated;
};

// calculate duration of given position from start to end minus pause
// return duration in hours
const positionDuration = (position) => {
	return (
		((new Date(position.finished_at)).getTime() - (new Date(position.started_at)).getTime()) / (1000 * 60 * 60)
	) - position.pause_duration;
};

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
    line4:  '#062D42',
    col1:   '#cccccc',
    col2:   '#dddddd',
    col3:   '#eeeeee',
    col4:   '#bbbbbb'
};
const config = {
    accountHolder: '{{ $this->settings["accountHolder"] }}',
    bank:          '{{ $this->settings["bank"] }}',
    bic:           '{{ $this->settings["bic"] }}',
    city:          '{{ $this->settings["city"] }}',
    company:       '{{ $this->settings["company"] }}',
    country:       '{{ $this->settings["country"] }}',
    email:         '{{ $this->settings["email"] }}',
    iban:          '{{ $this->settings["iban"] }}',
    logo:          '{{ $this->settings["logo"] }}',
    name:          '{{ $this->settings["name"] }}',
    phone:         '{{ $this->settings["phone"] }}',
    signature:     '{{ $this->settings["signature"] }}',
    street:        '{{ $this->settings["street"] }}',
    taxOffice:     '{{ $this->settings["taxOffice"] }}',
    vatId:         '{{ $this->settings["vatId"] }}',
    website:       '{{ $this->settings["website"] }}',
    zip:           '{{ $this->settings["zip"] }}',
};
const lang = '{{ $lang }}';
const client = {
    name:    decodeHtml('{{ $this->record->project->client->name }}'),
    address: decodeHtml('{{ str_replace("\n", "\\n", $this->record->project->client->full_address) }}'),
};
const label = {
    amountNet:          '{{ __("amountNet", [], $lang) }}',
    bank:               '{{ __("bank", [], $lang) }}',
    bic:                '{{ __("bic", [], $lang) }}',
    creadit:            '{{ __("credit", [], $lang) }}',
    dateAndDescription: '{{ __("dateAndDescription", [], $lang) }}',
    deliverables:       '{{ __("deliverables", [], $lang) }}',
    description:        '{{ __("description", [], $lang) }}',
    explanation:        '{{ __("invoice.explanation", [], $lang) }}',
    flatRate:           '{{ __("flatRate", [], $lang) }}',
    holder:             '{{ __("holder", [], $lang) }}',
    iban:               '{{ __("iban", [], $lang) }}',
    inHours:            '{{ __("inHours", [], $lang) }}',
    invoice:            '{{ trans_choice("invoice", 1, [], $lang) }}',
    invoiceDate:        '{{ __("invoiceDate", [], $lang) }}',
    invoiceNumber:      '{{ __("invoiceNumber", [], $lang) }}',
    page:               '{{ __("page", [], $lang) }} ',
    perHour:            '{{ __("perHour", [], $lang) }}',
    position:           '{{ trans_choice("position", 1, [], $lang) }}',
    price:              '{{ __("price", [], $lang) }}',
    quantity:           '{{ __("quantity", [], $lang) }}',
    regards:            '{{ __("withKindRegards", [], $lang) }}',
    statementOfWork:    '{{ __("statementOfWork", [], $lang) }}',
    sum:                '{{ __("sum", [], $lang) }}',
    sumOfAllPositions:  '{{ __("sumOfAllPositions", [], $lang) }}',
    taxOffice:          '{{ __("taxOffice", [], $lang) }}',
    to:                 '{{ __("to", [], $lang) }}',
    total:              '{{ __("total", [], $lang) }}',
    totalAmount:        '{{ __("totalAmount", [], $lang) }}',
    vat:                '{{ __("vat", [], $lang) }}',
    vatId:              '{{ __("vatId", [], $lang) }}',
};
const positions = JSON.parse("{{ $record->positions }}".replaceAll('&quot;', '"').replaceAll("\n", "\\n"));
const page = {
    current:   1,
    total:     paginatedPositions(positions).length + 1,
    rowHeight: 3.5,
};
const today = new Date();
const billedPerProject = {{ $this->record->pricing_unit->value === 'p' ? 'true' : 'false' }};
const invoice = {
    number:      '{{ $this->record->current_number }}',
    title:       '{{ $this->record->title }}',
    description: '{{ str_replace("\n", "\\n", $this->record->description) }}',
    hours:       nDigit(billedPerProject ? 1 : {{ $this->record->hours }}, 1, lang),
    price:       {{ $this->record->price }},
    net:         euro({{ $this->record->net }}, lang),
    vatRate:     percent({{ $this->record->vat_rate }}, lang),
    vat:         euro({{ $this->record->vat }}, lang),
    gross:       euro({{ $this->record->gross }}, lang),
    discount:    {{ (int)$this->record->discount }},
};

// add header to a given document
const pageHeader = (doc, title) => {
    return doc.setFillColor(colors.main).rect(0, 9, 210, 30, 'F')
        .addImage(config.logo, 'JPEG', 12, 13, 22, 22)
        .setTextColor(colors.light).setFont('FiraSansExtraLight')
            .setFontSize(26).text(title, 105, 27, { align: 'center' })
            .setFontSize(9)
                .text(config.email, 202, 25, { align: 'right' })
                .text(config.phone, 202, 19, { align: 'right' })
                .text(config.website, 202, 31, { align: 'right' });
};

// add footer to a given document
const pageFooter = (doc, showSignature=false) => {
    doc.setDrawColor(colors.line).setLineWidth(0.4).line(10, 277, 202, 277);
    if (showSignature) {
        doc.addImage(config.signature, 'PNG', 13, 262, 24, 18)
    }
    doc.setLineHeightFactor(1.3).setFontSize(9).setTextColor(colors.gray)
        .text(`${page.current}/${page.total}`, 103, 274, { align: 'right' })
        .text([config.name, `${config.city}, ${hdate(today, lang)}`], 10, 282)
        .text([label.iban, label.bic, label.bank], 90, 282, { align: 'right' })
        .text([label.vatId, label.taxOffice], 170, 282, { align: 'right' })
        .setFont('FiraSansRegular')
            .text([config.iban, config.bic, config.bank], 92, 282)
            .text([config.vatId, config.taxOffice], 172, 282);
    return doc;
};

document.addEventListener('DOMContentLoaded', () => {
    const { jsPDF } = window.jspdf;
    let doc = new jsPDF();

    // fonts
    doc.addFont('/fonts/FiraSans-Regular.ttf', 'FiraSansRegular', 'normal');
    doc.addFont('/fonts/FiraSans-ExtraLight.ttf', 'FiraSansExtraLight', 'normal');
    doc.addFont('/fonts/FiraSans-ExtraBold.ttf', 'FiraSansExtraBold', 'normal');

    /**
     * Cover Page
     */
    doc = pageHeader(doc, label.invoice.toUpperCase());
    // address header
    doc.setTextColor(colors.gray).setFont('FiraSansExtraLight').setFontSize(8)
        .text(`${config.name}, ${config.street}, ${config.zip} ${config.city}`, 10, 50)
        .setFontSize(9).text(label.to, 10, 62)
        .setTextColor(colors.main).setFontSize(15).text(client.name.toUpperCase(), 10, 69)
        .setDrawColor(colors.line).setLineWidth(0.4).line(0, 73, 70, 73).line(138, 73, 210, 73)
        .setTextColor(colors.gray)
            .setFontSize(10)
                .setLineHeightFactor(1.5).text(client.address, 10, 79)
                .text(label.invoiceNumber, 142, 62.8)
                .text(label.invoiceDate, 142, 68.8)
            .setFont('FiraSansRegular').setTextColor(colors.main)
                .text(invoice.number, 202, 62.8, { align: 'right' })
                .text(hdate(today, lang), 202, 68.8, { align: 'right' });
    // invoice table content
    doc.setLineWidth(0.8)
        .setFillColor(colors.col3).rect(10, 105, 90, 56, 'F').setDrawColor(colors.col2).line(10, 133, 100, 133)
        .setFillColor(colors.col2).rect(100, 105, 31, 56, 'F').setDrawColor(colors.col1).line(100, 133, 131, 133)
        .setFillColor(colors.col1).rect(131, 105, 30, 56, 'F').setDrawColor(colors.col4).line(131, 133, 162, 133)
        .setFillColor(colors.accent).rect(162, 105, 40, 56, 'F').setDrawColor(colors.line2).line(162, 133, 202, 133)
        .setFontSize(13)
            .setFont('FiraSansExtraLight').setTextColor(colors.dark)
                .text(label.description, 15, 118)
                .text(label.quantity, 115, 118, { align: 'center' })
                .text(label.price, 146, 118, { align: 'center' })
            .setFont('FiraSansRegular').setTextColor(colors.light)
                .text(label.total, 182, 118, { align: 'center' })
        .setFontSize(8)
            .setFont('FiraSansExtraLight').setTextColor(colors.dark)
                .text(label.statementOfWork, 15, 124)
                .text(billedPerProject ? label.flatRate : label.inHours, 115, 124, { align: 'center' })
                .text(billedPerProject ? label.flatRate : label.perHour, 146, 124, { align: 'center' })
            .setTextColor(colors.light)
                .text(billedPerProject ? label.sum : label.sumOfAllPositions, 182, 124, { align: 'center' })
        .setTextColor(colors.dark).setFont('FiraSansRegular').setFontSize(9).text(invoice.title, 15, 141)
        .setFont('FiraSansExtraLight')
            .setFontSize(8)
                .text(invoice.description, 15, 147)
            .setFontSize(16)
                .text(invoice.hours, 115, 148, { align: 'center' })
                .text(euro(invoice.price, lang), 146, 148, { align: 'center' })
            .setTextColor(colors.light)
                .text(invoice.net, 182, 148, { align: 'center' });
    // invoice table total without discount
    if (!invoice.discount) {
        doc.setFillColor(colors.main).rect(0, 165, 210, 50, 'F').setDrawColor(colors.line3).setLineWidth(0.3).line(124, 196, 194, 196)
            .setTextColor(colors.text)
                .setFont('FiraSansExtraLight').setFontSize(13)
                    .text(label.amountNet, 160, 181, { align: 'right' })
                    .text(`${invoice.vatRate} ${label.vat}`, 160, 190, { align: 'right' })
                    .text(invoice.net, 194, 181, { align: 'right' })
                    .text(invoice.vat, 194, 190, { align: 'right' })
            .setTextColor(colors.light)
                .setFont('FiraSansRegular').setFontSize(16)
                    .text(label.totalAmount, 160, 205, { align: 'right' })
                    .text(invoice.gross, 194, 205, { align: 'right' });
    }
    // invoice table total with discount
    else {
        doc.setFillColor(colors.main).rect(0, 165, 210, 50, 'F').setDrawColor(colors.line3).setLineWidth(0.3).line(124, 198, 194, 198)
            .setTextColor(colors.text)
                .setFont('FiraSansExtraLight').setFontSize(13)
                    .text(label.amountNet, 160, 177, { align: 'right' })
                    .text(label.credit, 160, 185, { align: 'right' })
                    .text(`${invoice.vatRate} ${label.vat}`, 160, 193, { align: 'right' })
                    .text(invoice.net, 194, 177, { align: 'right' })
                    .text(`– ${euro(invoice.discount, lang)}`, 194, 185, { align: 'right' })
                    .text(invoice.vat, 194, 193, { align: 'right' })
            .setTextColor(colors.light)
                .setFont('FiraSansRegular').setFontSize(16)
                    .text(label.totalAmount, 160, 207, { align: 'right' })
                    .text(invoice.gross, 194, 207, { align: 'right' });
    }
    // terms
    doc.setFontSize(10).setFont('FiraSansExtraLight').setTextColor(colors.dark)
        .text(markerReplace(label.explanation, [invoice.gross, invoice.number]), 10, 225, { maxWidth: 180 })
        .text([label.regards, config.name], 10, 244);
    // footer
    doc = pageFooter(doc, true);
    // document guides
    doc.setDrawColor(colors.line).line(0, 105, 3, 105).line(0, 148, 5, 148)
        .setDrawColor(colors.line4).line(0, 210, 3, 210)
    // go to next page
    page.current++;

    /**
     * Position pages for activity confirmation
     */
    paginatedPositions(positions).forEach(positions => {
        const totalHeight = positions.reduce((p, c) => p + c.description.split('\n').length + 2, 0)*page.rowHeight + 32;
        doc.addPage();
        // page header
        doc = pageHeader(doc, label.deliverables.toUpperCase());
        // position table content
        doc.setLineWidth(0.8)
            .setFillColor(colors.col3)
                .rect(10, 50, 113, totalHeight, 'F')
                .setDrawColor(colors.col2).line(10, 78, 123, 78)
            .setFillColor(billedPerProject ? colors.col3 : colors.col2)
                .rect(123, 50, 26, totalHeight, 'F')
                .setDrawColor(billedPerProject ? colors.col2 : colors.col1).line(123, 78, 149, 78)
            .setFillColor(billedPerProject ? colors.col3 : colors.col1)
                .rect(149, 50, 26, totalHeight, 'F')
                .setDrawColor(billedPerProject ? colors.col2 : colors.col4).line(149, 78, 176, 78)
            .setFillColor(billedPerProject ? colors.col3 : colors.accent)
                .rect(176, 50, 26, totalHeight, 'F')
                .setDrawColor(billedPerProject ? colors.col2 : colors.line2).line(176, 78, 202, 78)
            .setFontSize(13)
                .setFont('FiraSansExtraLight').setTextColor(colors.dark)
                    .text(label.position, 15, 63)
                    .text(billedPerProject ? '' : label.quantity, 136, 63, { align: 'center' })
                    .text(billedPerProject ? '' : label.price, 162, 63, { align: 'center' })
                .setFont('FiraSansRegular').setTextColor(colors.light)
                    .text(billedPerProject ? '' : label.total, 189, 63, { align: 'center' })
            .setFontSize(8)
                .setFont('FiraSansExtraLight').setTextColor(colors.dark)
                    .text(undated ? label.description : label.dateAndDescription, 15, 69)
                    .text(billedPerProject ? '' : label.inHours, 136, 69, { align: 'center' })
                    .text(billedPerProject ? '' : label.perHour, 162, 69, { align: 'center' })
                .setTextColor(colors.light)
                    .text(billedPerProject ? '' : label.price, 189, 69, { align: 'center' });
        // draw positions
        let linesProcessed = 0;
        positions.forEach((p, i) => {
            const posdate = hdate(new Date(p.started_at), lang);
            const poshours = positionDuration(p);
            const lineCount = p.description.trim().split('\n').length + 2;
            doc.setTextColor(colors.dark)
                .setFont('FiraSansRegular')
                    .setFontSize(9)
                        .text(
                            undated ? `${i+1}. ${label.position}` : posdate,
                            15,
                            (84+page.rowHeight*linesProcessed)
                        )
                .setFont('FiraSansExtraLight')
                    .setFontSize(8)
                        .text(p.description.trim(), 15, (88+page.rowHeight*linesProcessed))
                    .setFontSize(11)
                        .text(
                            billedPerProject ? '' : nDigit(poshours, 1, lang),
                            136,
                            (87+page.rowHeight*linesProcessed), { align: 'center' }
                        ).text(
                            billedPerProject ? '' : euro(invoice.price, lang),
                            162,
                            (87+page.rowHeight*linesProcessed), { align: 'center' }
                        )
                    .setTextColor(colors.light)
                        .text(
                            billedPerProject ? '' : euro(poshours*invoice.price, lang),
                            189,
                            (87+page.rowHeight*linesProcessed), { align: 'center' }
                        );
            linesProcessed += lineCount;
        })
        // footer
        doc = pageFooter(doc, page.current==page.total);
        // document guides
        doc.setDrawColor(colors.line).line(0, 105, 3, 105).line(0, 148, 5, 148).line(0, 210, 3, 210)
        // go to next page
        page.current++;
    });
    // serve document
    let blob = doc.output('blob');
    let blob_url = URL.createObjectURL(blob);
    let iframeElementContainer = document.getElementById('preview');
    iframeElementContainer.src=blob_url;
});
</script>
</x-filament-panels::page>
