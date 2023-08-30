<x-filament-panels::page>
    {{ $this->record->positions }}
    <script>
        // const { jsPDF } = require("jspdf"); // will automatically load the node version
        // import { jsPDF } from "jspdf";

        // Default export is a4 paper, portrait, using millimeters for units
        const doc = new jsPDF();

        doc.text("Hello world!", 10, 10);
        doc.save("a4.pdf");
    </script>
</x-filament-panels::page>
