"""Combine the fifteen existing practical reports into a submission PDF."""
from pathlib import Path

from pypdf import PdfReader, PdfWriter
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.platypus import Paragraph, SimpleDocTemplate, Spacer, Table, TableStyle


ROOT = Path(__file__).resolve().parents[1]
PDF_DIR = ROOT / "output" / "pdf"
OUTPUT = PDF_DIR / "D26DCE157_Practical_File_1-15.pdf"
TEMP_COVER = ROOT / "tmp" / "pdfs" / "practical-file-cover.pdf"

PRACTICALS = [
    (1, "Project Initiation, Requirements, Sitemap, Wireframes and GitHub Setup"),
    (2, "Semantic HTML5 Pages and Accessibility"),
    (3, "Responsive UI Design"),
    (4, "JavaScript DOM Manipulation and UI Interactivity"),
    (5, "Registration Form Validation"),
    (6, "Fetch API: Rendering, Search and Filter"),
    (7, "PHP Form Processing and File Storage"),
    (8, "MySQL Schema Design, ER Model and PDO"),
    (9, "Secure User Registration"),
    (10, "Secure Login, Sessions, Roles and Remember-Me"),
    (11, "Student Management CRUD with Search and Filter"),
    (12, "Event Management CRUD and Poster Upload"),
    (13, "PHP JSON API and Fetch-Based CRUD"),
    (14, "Admin Dashboard, Role-Based Access and Audit Log"),
    (15, "Final Integration, Testing, Documentation and Deployment"),
]


def create_cover(page_ranges):
    styles = getSampleStyleSheet()
    styles.add(ParagraphStyle(
        name="CoverTitle", parent=styles["Title"], fontName="Helvetica-Bold",
        fontSize=22, leading=27, textColor=colors.HexColor("#1d2a3d"),
        alignment=TA_CENTER, spaceAfter=7,
    ))
    styles.add(ParagraphStyle(
        name="CoverSub", parent=styles["Normal"], fontName="Times-Roman",
        fontSize=12, leading=16, textColor=colors.HexColor("#475569"),
        alignment=TA_CENTER, spaceAfter=4,
    ))
    styles.add(ParagraphStyle(
        name="TOCCell", parent=styles["Normal"], fontName="Times-Roman",
        fontSize=8.5, leading=10.5,
    ))
    styles.add(ParagraphStyle(
        name="TOCNum", parent=styles["TOCCell"], alignment=TA_CENTER,
    ))

    story = [
        Spacer(1, 15 * mm),
        Paragraph("STUDENTHUB PORTAL", styles["CoverTitle"]),
        Paragraph("WEB DEVELOPMENT FRAMEWORKS", styles["CoverSub"]),
        Spacer(1, 3 * mm),
        Paragraph("PRACTICAL FILE: PRACTICALS 1-15", styles["CoverSub"]),
        Paragraph("Student ID: D26DCE157", styles["CoverSub"]),
        Spacer(1, 8 * mm),
    ]
    rows = [[
        Paragraph("Practical", styles["TOCNum"]),
        Paragraph("Title", styles["TOCCell"]),
        Paragraph("PDF pages", styles["TOCNum"]),
    ]]
    for number, title in PRACTICALS:
        first, last = page_ranges[number]
        rows.append([
            Paragraph(str(number), styles["TOCNum"]),
            Paragraph(title, styles["TOCCell"]),
            Paragraph(f"{first}-{last}", styles["TOCNum"]),
        ])

    toc = Table(rows, colWidths=[25 * mm, 125 * mm, 30 * mm], repeatRows=1)
    toc.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#1d2a3d")),
        ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
        ("FONTNAME", (0, 0), (-1, 0), "Helvetica-Bold"),
        ("GRID", (0, 0), (-1, -1), 0.45, colors.HexColor("#cbd5e1")),
        ("BACKGROUND", (0, 1), (-1, -1), colors.HexColor("#f8fafc")),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("LEFTPADDING", (0, 0), (-1, -1), 7),
        ("RIGHTPADDING", (0, 0), (-1, -1), 7),
        ("TOPPADDING", (0, 0), (-1, -1), 4),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
    ]))
    story.extend([toc, Spacer(1, 5 * mm), Paragraph(
        "Each practical report follows in numerical order with its original report pages and included output evidence.",
        styles["CoverSub"],
    )])

    def footer(canvas, document):
        canvas.saveState()
        canvas.setFont("Times-Roman", 9)
        canvas.setFillColor(colors.HexColor("#475569"))
        canvas.drawCentredString(A4[0] / 2, 12 * mm, "D26DCE157 | Practical File 1-15 | Page 1")
        canvas.restoreState()

    TEMP_COVER.parent.mkdir(parents=True, exist_ok=True)
    SimpleDocTemplate(
        str(TEMP_COVER), pagesize=A4, leftMargin=15 * mm, rightMargin=15 * mm,
        topMargin=14 * mm, bottomMargin=18 * mm,
    ).build(story, onFirstPage=footer)


def main():
    readers = []
    page_ranges = {}
    next_page = 2  # The combined file starts with the contents/cover page.
    for number, _title in PRACTICALS:
        path = PDF_DIR / f"D26DCE157_(lab{number}).pdf"
        if not path.is_file():
            raise FileNotFoundError(f"Missing Practical {number} report: {path}")
        reader = PdfReader(str(path), strict=True)
        readers.append(reader)
        page_count = len(reader.pages)
        page_ranges[number] = (next_page, next_page + page_count - 1)
        next_page += page_count

    create_cover(page_ranges)
    writer = PdfWriter()
    writer.append(PdfReader(str(TEMP_COVER)))
    for (number, title), reader in zip(PRACTICALS, readers):
        start_index = len(writer.pages)
        writer.append(reader, outline_item=f"Practical {number}: {title}")
        if len(writer.pages) <= start_index:
            raise RuntimeError(f"Practical {number} did not append any pages")

    writer.add_metadata({
        "/Title": "StudentHub Portal Practical File - Practicals 1-15",
        "/Author": "D26DCE157",
        "/Subject": "Web Development Frameworks practical reports and outputs",
    })
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    with OUTPUT.open("wb") as stream:
        writer.write(stream)
    print(f"Created {OUTPUT}")
    print(f"Total pages: {len(writer.pages)}")
    for number, (first, last) in page_ranges.items():
        print(f"Practical {number}: pages {first}-{last}")


if __name__ == "__main__":
    main()
