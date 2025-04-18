<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
  exclude-result-prefixes="sitemap">

  <xsl:output method="html" indent="yes"/>

  <xsl:template match="/">
    <html>
      <head>
        <title>Sitemap - Review Hải Phòng</title>
        <style>
          :root {
            --main-bg: #eef2f7;
            --card-bg: #ffffff;
            --primary: #6c5ce7;
            --hover: #a29bfe;
            --text: #2d3436;
            --shadow: 0 10px 25px rgba(0,0,0,0.08);
          }

          body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background: var(--main-bg);
            margin: 0;
            padding: 3rem;
            color: var(--text);
          }

          h1 {
            text-align: center;
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 2rem;
          }

          .card {
            max-width: 1000px;
            margin: auto;
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
            animation: fadeIn 0.7s ease;
          }

          table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem;
          }

          th, td {
            padding: 18px 24px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
          }

          th {
            background: #f8f9fa;
            color: #636e72;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
          }

          td a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
          }

          td a:hover {
            color: var(--hover);
          }

          tr:hover {
            background-color: #fafafa;
            transition: background-color 0.3s ease;
          }

          @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
          }

          @media (max-width: 768px) {
            body { padding: 1rem; }
            table, thead, tbody, th, td, tr {
              display: block;
            }
            tr {
              margin-bottom: 1rem;
              border-bottom: 1px solid #ddd;
            }
            td {
              padding: 10px;
              display: flex;
              justify-content: space-between;
            }
            th {
              display: none;
            }
          }
        </style>
      </head>
      <body>
        <h1>Sitemap - Review Hải Phòng</h1>
        <table>
          <tr><th>STT</th><th>Đường dẫn bài viết</th><th>Lần sửa đổi cuối cùng</th></tr>
          <xsl:for-each select="sitemap:urlset/sitemap:url">
            <tr>
              <td><xsl:value-of select="position()"/></td>
              <td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>
              <td><xsl:value-of select="sitemap:lastmod"/></td>
            </tr>
          </xsl:for-each>
        </table>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
