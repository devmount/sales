@php
    $lang = $this->record?->client?->language ?? 'de';
@endphp

<x-filament-panels::page>
{{ __('downloadStartsAutomatically' )}}

<script>
// human readable date, e.g. '2. Januar 2022'
const hdate = (d, locale = 'de-DE') => {
    return d.toLocaleDateString(locale, { year: 'numeric', month: 'long', day: 'numeric' });
};

// short iso date, e.g. '20220102'
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

// sort estimates by weight
const sortedEstimates = (estimates) => {
    return estimates.slice().sort((a, b) => a.weight - b.weight);
};

// get estimates by page, one page has space for 50 lines (I know. Let me have my magic number.)
const paginatedEstimates = (estimates) => {
    const paginated = [];
    let linesProcessed = 0;
    sortedEstimates(estimates).forEach((e) => {
        const lineCount = e.description.split('\n').length + 2;
        linesProcessed += lineCount;
        const i = Math.floor(linesProcessed/50);
        if (i in paginated) {
            paginated[i].push(e);
        } else {
            paginated[i] = [e];
        };
    });
    return paginated;
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
        line4:  '#062D42',
        col1:   '#cccccc',
        col2:   '#dddddd',
        col3:   '#eeeeee',
        col4:   '#bbbbbb'
    };
    const config = {
        name:          '{{ $this->settings["name"] }}',
        company:       '{{ $this->settings["company"] }}',
        address:       '{{ $this->settings["address"] }}',
        email:         '{{ $this->settings["email"] }}',
        phone:         '{{ $this->settings["phone"] }}',
        website:       '{{ $this->settings["website"] }}',
        iban:          '{{ $this->settings["iban"] }}',
        bic:           '{{ $this->settings["bic"] }}',
        bank:          '{{ $this->settings["bank"] }}',
        accountHolder: '{{ $this->settings["accountHolder"] }}',
        taxOffice:     '{{ $this->settings["taxOffice"] }}',
        vatId:         '{{ $this->settings["vatId"] }}',
        vatRate:       '{{ $this->settings["vatRate"] }}',
        logo:          '{{ $this->settings["logo"] }}',
        signature:     '{{ $this->settings["signature"] }}',
    };
    const lang = '{{ $lang }}';
    const billedPerProject = {{ $this->record->pricing_unit->value === 'p' ? 'true' : 'false' }};
    const client = {
        name:    decodeHtml('{{ $this->record->client->name }}'),
        address: decodeHtml('{{ str_replace("\n", "\\n", $this->record->client->address) }}'),
    };
    const label = {
        amountNet:          '{{ __("amountNet", [], $lang) }}',
        bank:               '{{ __("bank", [], $lang) }}',
        bic:                '{{ __("bic", [], $lang) }}',
        billing:            '{{ __("hourBasedBilling", [], $lang) }}',
        credit:             '{{ __("credit", [], $lang) }}',
        costEstimate:       '{{ __("costEstimate", [], $lang) }}',
        description:        '{{ __("description", [], $lang) }}',
        holder:             '{{ __("holder", [], $lang) }}',
        iban:               '{{ __("iban", [], $lang) }}',
        inHours:            '{{ __("inHours", [], $lang) }}',
        inquiries:          '{{ __("inquiries", [], $lang) }}',
        otherClients:       '{{ __("otherClients", [], $lang) }}',
        page:               '{{ __("page", [], $lang) }} ',
        perHour:            '{{ __("perHour", [], $lang) }}',
        position:           '{{ trans_choice("position", 1, [], $lang) }}',
        price:              '{{ __("price", [], $lang) }}',
        quantity:           '{{ __("quantity", [], $lang) }}',
        quote:              '{{ __("quote", [], $lang) }}',
        regards:            '{{ __("withKindRegards", [], $lang) }}',
        scopeTimeRange:     billedPerProject ? '{{ __("projectScopeTimeRange", [], $lang) }}' : '{{ __("hourlyScopeTimeRange", [], $lang) }}',
        statementOfWork:    '{{ __("statementOfWork", [], $lang) }}',
        sum:                '{{ __("sum", [], $lang) }}',
        taxOffice:          '{{ __("taxOffice", [], $lang) }}',
        to:                 '{{ __("to", [], $lang) }}',
        total:              '{{ __("total", [], $lang) }}',
        totalAmount:        '{{ __("totalAmount", [], $lang) }}',
        totalQuote:         '{{ __("totalQuote", [], $lang) }}',
        vat:                '{{ __("vat", [], $lang) }}',
        vatId:              '{{ __("vatId", [], $lang) }}',
    };
    const estimates = JSON.parse("{{ $record->estimates }}".replaceAll('&quot;', '"').replaceAll("\n", "\\n"));
    const page = {
        current:   1,
        total:     paginatedEstimates(estimates).length + 1,
        rowHeight: 3.5,
    };
    const today = new Date();
    const quote = {
        title:       '{{ $this->record->title }}',
        description: '{{ str_replace("\n", "\\n", $this->record->description) }}',
        start:       new Date('{{ $this->record->start_at }}'),
        due:         new Date('{{ $this->record->due_at }}'),
        hours:       nDigit(billedPerProject ? {{ $this->record->scope }} : {{ $this->record->estimated_hours }}, 1, lang),
        price:       billedPerProject ? {{ $this->record->price/$this->record->scope }} : {{ $this->record->price }},
        net:         euro({{ $this->record->estimated_net }}, lang),
        vatRate:     percent(config.vatRate, lang),
        vat:         euro({{ $this->record->estimated_vat }}, lang),
        gross:       euro({{ $this->record->estimated_gross }}, lang),
    };

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // fonts
    doc.addFont('/fonts/FiraSans-Regular.ttf', 'FiraSansRegular', 'normal');
    doc.addFont('/fonts/FiraSans-ExtraLight.ttf', 'FiraSansExtraLight', 'normal');
    doc.addFont('/fonts/FiraSans-ExtraBold.ttf', 'FiraSansExtraBold', 'normal');
    // page header
    doc.setFillColor(colors.main).rect(0, 9, 210, 30, 'F');
    doc.addImage(config.logo, 'JPEG', 12, 13, 22, 22);
    doc.setTextColor(colors.light).setFont('FiraSansExtraLight')
        .setFontSize(26).text(label.quote.toUpperCase(), 105, 27, { align: 'center' })
        .setFontSize(9)
            .text(config.email, 202, 25, { align: 'right' })
            .text(config.phone, 202, 19, { align: 'right' })
            .text(config.website, 202, 31, { align: 'right' });
    // address header
    doc.setTextColor(colors.gray).setFont('FiraSansExtraLight').setFontSize(8)
        .text(config.address, 10, 50)
        .setFontSize(9).text(label.to, 10, 62)
        .setTextColor(colors.main).setFontSize(15).text(client.name.toUpperCase(), 10, 69)
        .setDrawColor(colors.line).setLineWidth(0.4).line(0, 73, 70, 73).line(140, 73, 210, 73)
        .setTextColor(colors.main)
            .setFontSize(10).setLineHeightFactor(1.5)
                .text(client.address, 10, 79)
            .setFont('FiraSansRegular')
                .text(hdate(today, lang), 202, 68.8, { align: 'right' });
    // quote table content
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
                .text(label.sum, 182, 118, { align: 'center' })
        .setFontSize(8)
            .setFont('FiraSansExtraLight').setTextColor(colors.dark)
                .text(label.statementOfWork, 15, 124)
                .text(label.inHours, 115, 124, { align: 'center' })
                .text(label.perHour, 146, 124, { align: 'center' })
            .setTextColor(colors.light)
                .text(label.totalQuote, 182, 124, { align: 'center' })
        .setTextColor(colors.dark).setFont('FiraSansRegular').setFontSize(9).text(quote.title, 15, 141)
        .setFont('FiraSansExtraLight')
            .setFontSize(8)
                .text(quote.description, 15, 147)
            .setFontSize(16)
                .text(quote.hours, 115, 148, { align: 'center' })
                .text(euro(quote.price, lang), 146, 148, { align: 'center' })
            .setTextColor(colors.light)
                .text(quote.net, 182, 148, { align: 'center' });
    // quote table total
    doc.setFillColor(colors.col3).rect(0, 165, 210, 50, 'F').setDrawColor(colors.col1).setLineWidth(0.3).line(124, 196, 194, 196)
        .setTextColor(colors.dark)
            .setFont('FiraSansExtraLight').setFontSize(13)
                .text(label.amountNet, 160, 181, { align: 'right' })
                .text(`${quote.vatRate} ${label.vat}`, 160, 190, { align: 'right' })
                .text(quote.net, 194, 181, { align: 'right' })
                .text(quote.vat, 194, 190, { align: 'right' })
            .setFont('FiraSansRegular').setFontSize(16)
                .text(label.totalAmount, 160, 205, { align: 'right' })
                .text(quote.gross, 194, 205, { align: 'right' });
    // terms
    doc.setFont('FiraSansExtraLight').setTextColor(colors.dark)
        .setFontSize(8).text(
            `${markerReplace(label.scopeTimeRange, [hdate(quote.start), hdate(quote.due)])} ${label.billing} ${label.otherClients} ${label.inquiries}`, 10, 225, { maxWidth: 180 }
        )
        .setFontSize(10).text([label.regards, config.name], 10, 244);
    // footer
    doc.setDrawColor(colors.line).setLineWidth(0.4).line(10, 272, 202, 272)
        .addImage(config.signature, 'PNG', 13, 255, 24, 18)
        .setLineHeightFactor(1.3).setFontSize(9).setTextColor(colors.gray)
            .text(`${label.page} ${page.current}/${page.total}`, 202, 290, { align: 'right' })
            .text('Berlin, ' + hdate(today, lang), 10, 277)
            .text([label.iban, label.bic, label.bank, label.holder], 90, 277, { align: 'right' })
            .text([label.vatId, label.taxOffice], 170, 277, { align: 'right' })
        .setFont('FiraSansRegular')
            .text([config.iban, config.bic, config.bank, config.accountHolder], 92, 277)
            .text([config.vatId, config.taxOffice], 172, 277);
    // document guides
    doc.setDrawColor(colors.line).line(0, 105, 3, 105).line(0, 148, 5, 148)
        .setDrawColor(colors.col1).line(0, 210, 3, 210)
    // handle next page
    page.current++;
    // add estimate pages for activity confirmation
    paginatedEstimates(estimates).forEach(estimates => {
        const totalHeight = estimates.reduce((p, c) => p + c.description.split('\n').length + 2, 0)*page.rowHeight + 32;
        doc.addPage();
        // page header
        doc.setFillColor(colors.main).rect(0, 9, 210, 30, 'F');
        doc.addImage(config.logo, 'JPEG', 12, 13, 22, 22);
        doc.setTextColor(colors.light).setFont('FiraSansExtraLight')
            .setFontSize(26).text(label.costEstimate.toUpperCase(), 105, 27, { align: 'center' })
            .setFontSize(9)
                .text(config.email, 202, 25, { align: 'right' })
                .text(config.phone, 202, 19, { align: 'right' })
                .text(config.website, 202, 31, { align: 'right' });
        // estimate table content
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
                    .text(label.description, 15, 69)
                    .text(billedPerProject ? '' : label.inHours, 136, 69, { align: 'center' })
                    .text(billedPerProject ? '' : label.perHour, 162, 69, { align: 'center' })
                .setTextColor(colors.light)
                    .text(billedPerProject ? '' : label.price, 189, 69, { align: 'center' });
        // draw estimates
        let linesProcessed = 0;
        estimates.forEach((e, i) => {
            const lineCount = e.description.split('\n').length + 2;
            doc.setTextColor(colors.dark)
                .setFont('FiraSansRegular')
                    .setFontSize(9)
                        .text(e.title, 15, (84+page.rowHeight*linesProcessed))
                .setFont('FiraSansExtraLight')
                    .setFontSize(8)
                        .text(e.description, 15, (88+page.rowHeight*linesProcessed))
                    .setFontSize(11)
                        .text(nDigit(e.amount, 1, lang), 136, (87+page.rowHeight*linesProcessed), { align: 'center' })
                        .text(
                            billedPerProject ? '' : euro(quote.price, lang),
                            162,
                            (87+page.rowHeight*linesProcessed), { align: 'center' }
                        )
                    .setTextColor(colors.light)
                        .text(
                            billedPerProject ? '' : euro(e.amount * quote.price, lang),
                            189,
                            (87+page.rowHeight*linesProcessed), { align: 'center' }
                        );
            linesProcessed += lineCount;
        })
        // footer
        doc.setDrawColor(colors.line).setLineWidth(0.4).line(10, 272, 202, 272)
            .addImage(config.signature, 'PNG', 13, 255, 24, 18)
            .setLineHeightFactor(1.3).setFontSize(9).setTextColor(colors.gray)
                .text(`${label.page} ${page.current}/${page.total}`, 202, 290, { align: 'right' })
                .text('Berlin, ' + hdate(today, lang), 10, 277)
                .text([label.iban, label.bic, label.bank, label.holder], 90, 277, { align: 'right' })
                .text([label.vatId, label.taxOffice], 170, 277, { align: 'right' })
            .setFont('FiraSansRegular')
                .text([config.iban, config.bic, config.bank, config.accountHolder], 92, 277)
                .text([config.vatId, config.taxOffice], 172, 277);
        // document guides
        doc.setDrawColor(colors.line).line(0, 105, 3, 105).line(0, 148, 5, 148).line(0, 210, 3, 210)
        // handle next page
        page.current++;
    });
    // serve document
    doc.save(
        `${isodate(today)}_${label.quote}_${config.company}.pdf`.toLowerCase(),
        { returnPromise: true }
    ).then(() => {
        setTimeout(() => { window.close() }, 100);
    });
});
</script>
</x-filament-panels::page>
