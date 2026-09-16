#!/usr/bin/env python3
"""Generate a monthly Devotee import workbook for the ttd_admin app.

Run:  python3 scripts/generate_devotee_monthly_import.py

Produces devotee_monthly_import.xlsx in the project root using only the
Python standard library (valid .xlsx = zipped OOXML parts). Headers match
the DevoteeImport WithHeadingRow format. Aadhaar numbers are written as
text so they never turn into scientific notation.
"""
import os
import textwrap
import zipfile
from xml.sax.saxutils import escape

OUT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT_FILE = os.path.join(OUT_DIR, "devotee_monthly_import.xlsx")

HEADERS = [
    "Name", "Age", "Gender", "Aadhaar", "Phone", "Email",
    "City", "State", "Pincode", "Gothram", "Remarks", "Referred",
]

# Columns widths (1-based, Excel char units)
COL_WIDTHS = {
    1: 24, 2: 6, 3: 10, 4: 16, 5: 16, 6: 24,
    7: 16, 8: 16, 9: 10, 10: 14, 11: 75, 12: 16,
}

# ---------------------------------------------------------------------------
# Monthly data -----------------------------------------------------------------
# ---------------------------------------------------------------------------
# Each group: referred = agent/referrer name (empty => standalone), remarks =
# monthly booking context. People tuples: (name, age, gender, aadhaar)
GROUPS = [
    {
        "referred": "Geetha",
        "remarks": "5 persons - December bookings. Amount Rs.4,000 received by Balaji. Prefer 4-6 or 11-13 Dec (Vaikunta Ekadasi, Fri-Sun); fallback weekend Fri-Sun.",
        "people": [
            ("Geetha Sai Sree", "25", "Female", "872712559090"),
            ("S Murthy", "60", "Male", "307267271224"),
            ("Gayatri", "54", "Female", "289491699671"),
            ("Ramakrishna Sastry", "29", "Male", "329601228567"),
            ("Umadevi", "55", "Female", "982595771367"),
        ],
    },
    {
        "referred": "Balaji",
        "remarks": "3 persons - Virtual seva on 8 Dec 2026. Amount received. AC deluxe room accommodation at TTD complex Tirupati. Check-in 8 Dec 7:00 AM.",
        "people": [
            ("Ravi Selvarajan", "57", "Male", "358249304587"),
            ("Ashwin Ravi", "22", "Male", "933686289111"),
            ("Indumathi", "46", "Female", "461448565206"),
        ],
    },
    {
        "referred": "Balaji",
        "remarks": "5 persons - December 31 darshan ticket (no darshan booking reference). Amount received.",
        "people": [
            ("Deepak Digambar", "42", "Male", "690897441065"),
            ("Prashant Anant", "45", "Male", "941937193927"),
            ("Atul Ashok", "37", "Male", "930818726294"),
            ("Parmanand Jaysing", "43", "Male", "424900499394"),
            ("Omkar Gangadhar", "42", "Male", "340161892212"),
        ],
    },
    {
        "referred": "Balajeeswamy",
        "remarks": "5 members - Vaikunta Ekadasi ticket booking. Contact Balajeeswamy 9341232168. Amount NOT received.",
        "people": [
            ("Jyoti Bai", "82", "Female", "439894627706"),
            ("Vanitha Prakasam", "60", "Female", "608837899906"),
            ("Suguna", "67", "Female", "416254023271"),
            ("Shobha", "50", "Female", "334317836100"),
            ("Bharathi Madhusudan", "58", "Female", "319523457656"),
        ],
    },
    {
        "referred": "Ayyapan",
        "remarks": "4 persons - Homam tickets after 20 Oct. Contact Ayyapan 9845569072.",
        "people": [
            ("Shanthi", "60", "Female", "802910307454"),
            ("Balaji", "40", "Male", "562745923180"),
            ("Hemalatha", "31", "Female", "858032929728"),
            ("Lakshmi", "56", "Female", "466276815300"),
        ],
    },
    {
        "referred": "Ayyapan",
        "remarks": "8 persons - Homam. Contact Ayyapan 9845569072.",
        "people": [
            ("Nikhil Bhonsale", "27", "Male", "745270181085"),
            ("Thriveni", "58", "Male", "292385632941"),
            ("Jasheel Ksheersagar", "19", "Male", "396280289429"),
            ("Praveen Kumar", "53", "Male", "400081564721"),
            ("Vinutha", "50", "Female", "681353588188"),
            ("Narendranath", "63", "Male", "733301053656"),
            ("Gowri Bhonsale", "61", "Female", "330794773164"),
            ("Chandra Mouli", "67", "Male", "719662281779"),
        ],
    },
    {
        "referred": "Ayyapan",
        "remarks": "2 persons - 11 Dec (Friday), on Apurba 8074728077. Amount received. Contact Ayyapan 9845569072.",
        "people": [
            ("Vijaya Lakshmi", "39", "Female", "655048701668"),
            ("Apurba Bhattacharjee", "37", "Male", "763258822819"),
        ],
    },
    {
        "referred": "Yashaswini",
        "remarks": "3 persons - Need Dec 19 or 26. Amount received. Contact Yashu 8095424638.",
        "people": [
            ("Pramoda", "35", "Female", "989963713049"),
            ("Gjrijamma Shedthi", "64", "Female", "667858205287"),
            ("Subhashchandra R Shetty", "49", "Male", "796337684212"),
        ],
    },
    {
        "referred": "Yashaswini",
        "remarks": "8 persons - Virtual seva December month, 18/12/2026. Amount NOT received. Contact 9595128088.",
        "people": [
            ("Ashwini Amol", "31", "Female", "426168138338"),
            ("Amol Sadashiv", "37", "Male", "965227308100"),
            ("Praful Ashok", "38", "Male", "653773828393"),
            ("Prathiksha Praful", "29", "Female", "553445542735"),
            ("Akshay Ramesh", "31", "Male", "478894594126"),
            ("Saloni Akshay", "22", "Female", "363559474018"),
            ("Nupur Roshan", "36", "Female", "648156622053"),
            ("Roshan Ashok", "39", "Male", "619455715199"),
        ],
    },
    {
        "referred": "",
        "remarks": "2 persons. Contact Subramanya Shetty 9739680772.",
        "people": [
            ("Nethravathi", "52", "Female", "411376278314"),
            ("Shiva Prasad", "29", "Male", "720812145383"),
        ],
    },
    {
        "referred": "Ayyapan",
        "remarks": "4 persons - Supatham entry / Arjitha Seva. Date 14-20 Dec; prefer 14 Dec. Contact Ayyappan 9845569072.",
        "people": [
            ("Cheekatla Vyaghri", "51", "Male", "267551414031"),
            ("Cheekatla Venkatalakshmi", "42", "Female", "617243486393"),
            ("Cheekatla Sree Harshini", "13", "Female", "574166905153"),
            ("Cheekatla Damini", "17", "Female", "621715148624"),
        ],
    },
    {
        "referred": "",
        "remarks": "4 persons - Homam tickets on 5, 6, 7 Oct 2026.",
        "people": [
            ("Ulhas Dattatray", "64", "Male", "633697922048"),
            ("Mahesh Murlidhar", "59", "Male", "954824980698"),
            ("Sanjay Mahadev", "53", "Male", "940794111900"),
            ("Krishna Bhagwant", "56", "Male", "707869243079"),
        ],
    },
    {
        "referred": "Ayyapan",
        "remarks": "7 persons - Senior citizens. Dec 15, 16, 17; any date ok. Contact Ayyapan 9845569072.",
        "people": [
            ("Sumathy", "63", "Female", "582992181047"),
            ("Murali Mohan", "71", "Male", "478206467166"),
            ("Mohan", "69", "Male", "360436937691"),
            ("Chandra Kantha", "62", "Female", "906959094885"),
            ("Saralamma", "79", "Female", "863688424260"),
            ("Laxminath", "61", "Female", "345405135648"),
            ("Vishwanathaiah Setty", "71", "Male", "851942491795"),
        ],
    },
]


def rows():
    """Flatten groups into rows: [Name, Age, Gender, Aadhaar, Phone, Email,
    City, State, Pincode, Gothram, Remarks, Referred]"""
    out = []
    for g in GROUPS:
        for (name, age, gender, aadhaar) in g["people"]:
            out.append([
                name.strip(),
                age.strip(),
                gender.strip(),
                aadhaar.strip(),
                "", "", "", "", "", "",
                g["remarks"],
                g["referred"].strip(),
            ])
    return out


# ---------------------------------------------------------------------------
# Minimal OOXML writer ------------------------------------------------------
def _shared_strings_xml(values):
    counts = {}
    order = []
    for v in values:
        counts[v] = counts.get(v, 0) + 1
        if counts[v] == 1:
            order.append(v)
    items = "".join(
        f'<si><t xml:space="preserve">{escape(v)}</t></si>' for v in order
    )
    return (f'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            f'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            f'count="{len(values)}" uniqueCount="{len(order)}">{items}</sst>'), order


def _styles_xml():
    # fonts: 0 normal, 1 bold white
    fonts = (
        '<font><sz val="11"/><name val="Calibri"/></font>'
        '<font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font>'
    )
    # fills: 0 none, 1 gray125 (required default), 2 solid maroon
    fills = (
        '<fill><patternFill patternType="none"/></fill>'
        '<fill><patternFill patternType="gray125"/></fill>'
        '<fill><patternFill patternType="solid"><fgColor rgb="FF800000"/>'
        '<bgColor indexed="64"/></patternFill></fill>'
    )
    # borders: 0 none, 1 thin all
    borders = (
        '<border><left/><right/><top/><bottom/><diagonal/></border>'
        '<border><left style="thin"><color rgb="FFD8D8D8"/></left>'
        '<right style="thin"><color rgb="FFD8D8D8"/></right>'
        '<top style="thin"><color rgb="FFD8D8D8"/></top>'
        '<bottom style="thin"><color rgb="FFD8D8D8"/></bottom><diagonal/></border>'
    )
    # cellXfs: 0 default, 1 header, 2 data wrap
    cellxfs = (
        '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1" applyFill="1" applyBorder="1">'
        '<alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1">'
        '<alignment vertical="top" wrapText="1"/></xf>'
    )
    return ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            f'<fonts count="2">{fonts}</fonts><fills count="3">{fills}</fills>'
            f'<borders count="2">{borders}</borders><cellStyleXfs count="1">'
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            f'<cellXfs count="3">{cellxfs}</cellXfs>'
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            '</styleSheet>')


def _worksheet_xml(data, order):
    cols = "".join(
        f'<col min="{i}" max="{i}" width="{COL_WIDTHS.get(i, 12)}" customWidth="1"/>'
        for i in range(1, len(HEADERS) + 1)
    )
    rows_xml = []
    # header row (style 1)
    cells = []
    for j, _ in enumerate(HEADERS):
        idx = order.index(HEADERS[j])
        ref = f"{chr(65 + j)}1"
        cells.append(f'<c r="{ref}" s="1" t="s"><v>{idx}</v></c>')
    rows_xml.append(f'<row r="1" ht="22" customHeight="1">{"".join(cells)}</row>')
    # data rows (style 2)
    for i, row in enumerate(data, start=2):
        cells = []
        for j, val in enumerate(row):
            sval = val if val else ""
            idx = order.index(sval)
            ref = f"{chr(65 + j)}{i}"
            cells.append(f'<c r="{ref}" s="2" t="s"><v>{idx}</v></c>')
        rows_xml.append(f'<row r="{i}">{"".join(cells)}</row>')
    return ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            '<sheetViews><sheetView tabSelected="1" workbookViewId="0">'
            '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            '</sheetView></sheetViews>'
            '<sheetFormatPr defaultRowHeight="15"/>'
            f'<cols>{cols}</cols>'
            f'<sheetData>{"".join(rows_xml)}</sheetData>'
            '</worksheet>')


def build_xlsx(path, data):
    sst_xml, order = _shared_strings_xml([v for row in ([HEADERS] + data) for v in row])

    files = {
        "[Content_Types].xml": (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            '<Default Extension="xml" ContentType="application/xml"/>'
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            '</Types>'
        ),
        "_rels/.rels": (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            '</Relationships>'
        ),
        "xl/workbook.xml": (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            '<sheets><sheet name="Devotees" sheetId="1" r:id="rId1"/></sheets>'
            '</workbook>'
        ),
        "xl/_rels/workbook.xml.rels": (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            '</Relationships>'
        ),
        "xl/worksheets/sheet1.xml": _worksheet_xml(data, order),
        "xl/styles.xml": _styles_xml(),
        "xl/sharedStrings.xml": sst_xml,
    }

    with zipfile.ZipFile(path, "w", zipfile.ZIP_DEFLATED) as zf:
        for name, content in files.items():
            zf.writestr(name, content)


def main():
    data = rows()
    build_xlsx(OUT_FILE, data)
    print(f"Generated: {OUT_FILE}")
    print(f"Header row: {len(HEADERS)} columns")
    print(f"Data rows : {len(data)}")

    # quick summary per referred agent
    from collections import Counter
    counter = Counter(r[11] for r in data)
    for agent, count in counter.most_common():
        print(f"  Referred <{agent or '(none)'}>: {count} person(s)")


if __name__ == "__main__":
    main()