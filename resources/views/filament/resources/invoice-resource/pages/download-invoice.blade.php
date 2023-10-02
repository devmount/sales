@php
    $lang = $this->record?->project?->client?->language ?? 'de';
@endphp

<x-filament-panels::page>
{{ __('downloadStartsAutomatically' )}}

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
        const lineCount = p.description.split('\n').length + 2;
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
        logo:          '{{ $this->settings["logo"] }}',
        signature:     '{{ $this->settings["signature"] }}',
    };
    const positions = JSON.parse("{{ $record->positions }}".replaceAll('&quot;', '"').replaceAll("\n", "\\n"));
    const positionRowHeight = 3.5;
    let page = 1;
    const totalPageCount = paginatedPositions(positions).length + 1;
    const today = new Date();
    const invoiceNumber = '{{ $this->record->current_number }}';
    const billedPerProject = {{ $this->record->pricing_unit === 'p' ? 'true' : 'false' }};
    const discount = {{ (int)$this->record->discount }};

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // fonts
    doc.addFont('/fonts/FiraSans-Regular.ttf', 'FiraSansRegular', 'normal');
    doc.addFont('/fonts/FiraSans-ExtraLight.ttf', 'FiraSansExtraLight', 'normal');
    doc.addFont('/fonts/FiraSans-ExtraBold.ttf', 'FiraSansExtraBold', 'normal');
    // document guides
    // doc.line(0, 105, 3, 105).line(0, 148, 5, 148).line(0, 210, 3, 210)
    // page header
    doc.setFillColor(colors.main).rect(0, 9, 210, 30, 'F');
    doc.addImage(config.logo, 'JPEG', 12, 13, 22, 22);
    doc.setTextColor(colors.light).setFont('FiraSansExtraLight')
        .setFontSize(26).text('{{ trans_choice("invoice", 1, [], $lang) }}'.toUpperCase(), 105, 27, { align: 'center' })
        .setFontSize(9)
            .text(config.email, 202, 25, { align: 'right' })
            .text(config.phone, 202, 19, { align: 'right' })
            .text(config.website, 202, 31, { align: 'right' });
    // address header
    doc.setTextColor(colors.gray).setFont('FiraSansExtraLight').setFontSize(8)
        .text(config.address, 10, 50)
        .setFontSize(9).text('{{ __("to", [], $lang) }}', 10, 62)
        .setTextColor(colors.main).setFontSize(15).text('{{ $this->record->project->client->name }}'.toUpperCase(), 10, 69)
        .setDrawColor(colors.line).setLineWidth(0.4).line(0, 73, 70, 73).line(140, 73, 210, 73)
        .setTextColor(colors.gray)
            .setFontSize(10)
                .setLineHeightFactor(1.5).text('{{ str_replace("\n", "\\n", $this->record->project->client->address) }}', 10, 79)
                .text('{{ __("invoiceNumber", [], $lang) }}', 144, 62.8)
                .text('{{ __("invoiceDate", [], $lang) }}', 144, 68.8)
            .setFont('FiraSansRegular').setTextColor(colors.main)
                .text(invoiceNumber, 202, 62.8, { align: 'right' })
                .text(hdate(today, '{{ $lang }}'), 202, 68.8, { align: 'right' });
    // invoice table content
    doc.setLineWidth(0.8)
        .setFillColor(colors.col3).rect(10, 105, 90, 56, 'F').setDrawColor(colors.col2).line(10, 133, 100, 133)
        .setFillColor(colors.col2).rect(100, 105, 31, 56, 'F').setDrawColor(colors.col1).line(100, 133, 131, 133)
        .setFillColor(colors.col1).rect(131, 105, 30, 56, 'F').setDrawColor(colors.col4).line(131, 133, 162, 133)
        .setFillColor(colors.accent).rect(162, 105, 40, 56, 'F').setDrawColor(colors.line2).line(162, 133, 202, 133)
        .setFontSize(13)
            .setFont('FiraSansExtraLight').setTextColor(colors.dark)
                .text('{{ __("description", [], $lang) }}', 15, 118)
                .text('{{ __("quantity", [], $lang) }}', 115, 118, { align: 'center' })
                .text('{{ __("price", [], $lang) }}', 146, 118, { align: 'center' })
            .setFont('FiraSansRegular').setTextColor(colors.light)
                .text('{{ __("total", [], $lang) }}', 182, 118, { align: 'center' })
        .setFontSize(8)
            .setFont('FiraSansExtraLight').setTextColor(colors.dark)
                .text('{{ __("statementOfWork", [], $lang) }}', 15, 124)
                .text(billedPerProject ? '{{ __("flatRate", [], $lang) }}' : '{{ __("inHours", [], $lang) }}', 115, 124, { align: 'center' })
                .text(billedPerProject ? '{{ __("flatRate", [], $lang) }}' : '{{ __("perHour", [], $lang) }}', 146, 124, { align: 'center' })
            .setTextColor(colors.light)
                .text(billedPerProject ? '{{ __("sum", [], $lang) }}' : '{{ __("sumOfAllPositions", [], $lang) }}', 182, 124, { align: 'center' })
        .setTextColor(colors.dark).setFont('FiraSansRegular').setFontSize(9).text('{{ $this->record->title }}', 15, 141)
        .setFont('FiraSansExtraLight')
            .setFontSize(8)
                .text('{{ str_replace("\n", "\\n", $this->record->description) }}', 15, 147)
            .setFontSize(16)
                .text(nDigit(billedPerProject ? 1 : {{ $this->record->hours }}, 1, '{{ $lang }}'), 115, 148, { align: 'center' })
                .text(euro({{ $this->record->price }}, '{{ $lang }}'), 146, 148, { align: 'center' })
            .setTextColor(colors.light)
                .text(euro({{ $this->record->net }}, '{{ $lang }}'), 182, 148, { align: 'center' });
    // invoice table total without discount
    if (!discount) {
        doc.setFillColor(colors.main).rect(0, 165, 210, 50, 'F').setDrawColor(colors.line3).setLineWidth(0.3).line(124, 196, 194, 196)
            .setTextColor(colors.text)
                .setFont('FiraSansExtraLight').setFontSize(13)
                    .text('{{ __("amountNet", [], $lang) }}', 160, 181, { align: 'right' })
                    .text(percent('{{ $this->record->vat_rate }}', '{{ $lang }}') + ' {{ __("vat", [], $lang) }}', 160, 190, { align: 'right' })
                    .text(euro({{ $this->record->net }}, '{{ $lang }}'), 194, 181, { align: 'right' })
                    .text(euro({{ $this->record->vat }}, '{{ $lang }}'), 194, 190, { align: 'right' })
            .setTextColor(colors.light)
                .setFont('FiraSansRegular').setFontSize(16)
                    .text('{{ __("totalAmount", [], $lang) }}', 160, 205, { align: 'right' })
                    .text(euro({{ $this->record->gross }}, '{{ $lang }}'), 194, 205, { align: 'right' });
    }
    // invoice table total with discount
    else {
        doc.setFillColor(colors.main).rect(0, 165, 210, 50, 'F').setDrawColor(colors.line3).setLineWidth(0.3).line(124, 198, 194, 198)
            .setTextColor(colors.text)
                .setFont('FiraSansExtraLight').setFontSize(13)
                    .text('{{ __("amountNet", [], $lang) }}', 160, 177, { align: 'right' })
                    .text('{{ __("credit", [], $lang) }}', 160, 185, { align: 'right' })
                    .text(percent('{{ $this->record->vat_rate }}', '{{ $lang }}') + ' {{ __("vat", [], $lang) }}', 160, 193, { align: 'right' })
                    .text(euro({{ $this->record->net }}, '{{ $lang }}'), 194, 177, { align: 'right' })
                    .text('–' + euro(discount, '{{ $lang }}'), 194, 185, { align: 'right' })
                    .text(euro({{ $this->record->vat }}, '{{ $lang }}'), 194, 193, { align: 'right' })
            .setTextColor(colors.light)
                .setFont('FiraSansRegular').setFontSize(16)
                    .text('{{ __("totalAmount", [], $lang) }}', 160, 207, { align: 'right' })
                    .text(euro({{ $this->record->gross }}, '{{ $lang }}'), 194, 207, { align: 'right' });
    }
    // terms
    doc.setFontSize(10).setFont('FiraSansExtraLight').setTextColor(colors.dark)
        .text(markerReplace(
            '{{ __("invoice.explanation", [], $lang) }}',
            [euro({{ $this->record->gross }}, '{{ $lang }}'), invoiceNumber]
        ), 10, 225, { maxWidth: 180 })
        .text(['{{ __("withKindRegards", [], $lang) }}', config.name], 10, 244);
    // footer
    doc.setDrawColor(colors.line).setLineWidth(0.4).line(10, 272, 202, 272)
        .addImage(config.signature, 'PNG', 13, 255, 24, 18)
        .setLineHeightFactor(1.3).setFontSize(9).setTextColor(colors.gray)
            .text('{{ __("page", [], $lang) }} ' + page + '/' + totalPageCount, 202, 290, { align: 'right' })
            .text('Berlin, ' + hdate(today, '{{ $lang }}'), 10, 277)
            .text(['{{ __("iban", [], $lang) }}', '{{ __("bic", [], $lang) }}', '{{ __("bank", [], $lang) }}', '{{ __("holder", [], $lang) }}'], 90, 277, { align: 'right' })
            .text(['{{ __("vatId", [], $lang) }}', '{{ __("taxOffice", [], $lang) }}'], 170, 277, { align: 'right' })
        .setFont('FiraSansRegular')
            .text([config.iban, config.bic, config.bank, config.accountHolder], 92, 277)
            .text([config.vatId, config.taxOffice], 172, 277);
    page++;
    // add position pages for activity confirmation
    paginatedPositions(positions).forEach(positions => {
        const totalHeight = positions.reduce((p, c) => p + c.description.split('\n').length + 2, 0)*positionRowHeight + 32;
        doc.addPage();
        // page header
        doc.setFillColor(colors.main).rect(0, 9, 210, 30, 'F');
        doc.addImage(config.logo, 'JPEG', 12, 13, 22, 22);
        doc.setTextColor(colors.light).setFont('FiraSansExtraLight')
            .setFontSize(26).text('{{ __("deliverables", [], $lang) }}'.toUpperCase(), 105, 27, { align: 'center' })
            .setFontSize(9)
                .text(config.email, 202, 25, { align: 'right' })
                .text(config.phone, 202, 19, { align: 'right' })
                .text(config.website, 202, 31, { align: 'right' });
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
                    .text('{{ trans_choice("position", 1, [], $lang) }}', 15, 63)
                    .text(billedPerProject ? '' : '{{ __("quantity", [], $lang) }}', 136, 63, { align: 'center' })
                    .text(billedPerProject ? '' : '{{ __("price", [], $lang) }}', 162, 63, { align: 'center' })
                .setFont('FiraSansRegular').setTextColor(colors.light)
                    .text(billedPerProject ? '' : '{{ __("total", [], $lang) }}', 189, 63, { align: 'center' })
            .setFontSize(8)
                .setFont('FiraSansExtraLight').setTextColor(colors.dark)
                    .text(undated ? '{{ __("description", [], $lang) }}' : '{{ __("dateAndDescription", [], $lang) }}', 15, 69)
                    .text(billedPerProject ? '' : '{{ __("inHours", [], $lang) }}', 136, 69, { align: 'center' })
                    .text(billedPerProject ? '' : '{{ __("perHour", [], $lang) }}', 162, 69, { align: 'center' })
                .setTextColor(colors.light)
                    .text(billedPerProject ? '' : '{{ __("price", [], $lang) }}', 189, 69, { align: 'center' });
        // draw positions
        let linesProcessed = 0;
        positions.forEach((p, i) => {
            const posdate = hdate(new Date(p.started_at), '{{ $lang }}');
            const poshours = positionDuration(p);
            const lineCount = p.description.split('\n').length + 2;
            doc.setTextColor(colors.dark)
                .setFont('FiraSansRegular')
                    .setFontSize(9)
                        .text(
                            undated ? (i+1).toString() + '. {{ trans_choice("position", 1, [], $lang) }}' : posdate,
                            15,
                            (84+positionRowHeight*linesProcessed)
                        )
                .setFont('FiraSansExtraLight')
                    .setFontSize(8)
                        .text(p.description, 15, (88+positionRowHeight*linesProcessed))
                    .setFontSize(11)
                        .text(
                            billedPerProject ? '' : nDigit(poshours, 1, '{{ $lang }}'),
                            136,
                            (87+positionRowHeight*linesProcessed), { align: 'center' }
                        ).text(
                            billedPerProject ? '' : euro({{ $this->record->price }}, '{{ $lang }}'),
                            162,
                            (87+positionRowHeight*linesProcessed), { align: 'center' }
                        )
                    .setTextColor(colors.light)
                        .text(
                            billedPerProject ? '' : euro(poshours*{{ $this->record->price }}, '{{ $lang }}'),
                            189,
                            (87+positionRowHeight*linesProcessed), { align: 'center' }
                        );
            linesProcessed += lineCount;
        })
        // footer
        doc.setDrawColor(colors.line).setLineWidth(0.4).line(10, 272, 202, 272)
            .addImage(config.signature, 'PNG', 13, 255, 24, 18)
            .setLineHeightFactor(1.3).setFontSize(9).setTextColor(colors.gray)
                .text('{{ __("page", [], $lang) }} ' + page + '/' + totalPageCount, 202, 290, { align: 'right' })
                .text('Berlin, ' + hdate(today, '{{ $lang }}'), 10, 277)
                .text(['{{ __("iban", [], $lang) }}', '{{ __("bic", [], $lang) }}', '{{ __("bank", [], $lang) }}', '{{ __("holder", [], $lang) }}'], 90, 277, { align: 'right' })
                .text(['{{ __("vatId", [], $lang) }}', '{{ __("taxOffice", [], $lang) }}'], 170, 277, { align: 'right' })
            .setFont('FiraSansRegular')
                .text([config.iban, config.bic, config.bank, config.accountHolder], 92, 277)
                .text([config.vatId, config.taxOffice], 172, 277);
        page++;
    });
    // serve document
    doc.save(
        `${invoiceNumber}_{{ trans_choice("invoice", 1, [], $lang) }}_${config.company}.pdf`.toLowerCase(),
        { returnPromise: true }
    ).then(() => {
        setTimeout(() => { window.close() }, 100);
    });
});
</script>
</x-filament-panels::page>
