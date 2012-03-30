<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="text" />
	<xsl:template match="/">
		<xsl:for-each select="database/table">
			<xsl:text>CREATE TABLE </xsl:text>
			<xsl:value-of select="@name" />
			<xsl:text> (&#10;</xsl:text>

			<xsl:for-each select="./column">
				<xsl:if test="position() != 1">
					<xsl:text>,&#10;</xsl:text>
				</xsl:if>
				<xsl:text>&#9;`</xsl:text><xsl:value-of select="@name" /><xsl:text>`</xsl:text>
				<xsl:choose>
					<xsl:when test="@type='VARCHAR'">
						<xsl:text> VARCHAR</xsl:text>
					</xsl:when>
					<xsl:when test="@type='INTEGER'">
						<xsl:text> INT</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text> VARCHAR</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
				<xsl:if test="(@type = 'TINYINT' or @type = 'SMALLINT' or @type = 'INTEGER' or @type = 'BIGINT' or @type = 'FLOAT' or @type = 'REAL' or @type = 'DOUBLE' or @type = 'NUMERIC' or @type = 'DECIMAL' or @type = 'CHAR' or @type = 'VARCHAR' or @type = 'LONGVARCHAR' or @type = 'BINARY' or @type = 'VARBINARY' or @type = 'LONGVARBINARY') and current()[@size]">
					<xsl:text>(</xsl:text>
					<xsl:value-of select="@size" />
					<xsl:text>)</xsl:text>
				</xsl:if>
				<xsl:if test="current()[@required] and @required='true'">
					<xsl:text> NOT NULL</xsl:text>
				</xsl:if>
				<xsl:if test="current()[@default]">
					<xsl:text> DEFAULT '</xsl:text>
					<xsl:value-of select="@default" />
					<xsl:text>'</xsl:text>
				</xsl:if>
				<xsl:if test="current()[@primaryKey] and @primaryKey='true'">
					<xsl:text> AUTO_INCREMENT</xsl:text>
				</xsl:if>
				<xsl:if test="current()[@autoIncrement] and @autoIncrement='true'">
					<xsl:text> PRIMARY KEY</xsl:text>
				</xsl:if>
				<xsl:if test="current()[@description]">
					<xsl:text> COMMENT '</xsl:text>
					<xsl:value-of select="@description" />
					<xsl:text>'</xsl:text>
				</xsl:if>
			</xsl:for-each>

			<xsl:for-each select="./index">
				<xsl:text>,&#10;&#9;INDEX</xsl:text>
				<xsl:if test="current()[@name]">
					<xsl:text> `</xsl:text>
					<xsl:value-of select="@name" />
					<xsl:text>`</xsl:text>
				</xsl:if>
				<xsl:text> (</xsl:text>
				<xsl:for-each select="./index-column">
					<xsl:if test="position() != 1">
						<xsl:text>,</xsl:text>
					</xsl:if>
					<xsl:text>`</xsl:text>
					<xsl:value-of select="@name" />
					<xsl:text>`</xsl:text>
					<xsl:if test="current()[@size]">
						<xsl:text>(</xsl:text>
						<xsl:value-of select="@size" />
						<xsl:text>)</xsl:text>
					</xsl:if>
				</xsl:for-each>
				<xsl:text>)</xsl:text>
			</xsl:for-each>

			<xsl:for-each select="./unique">
				<xsl:text>,&#10;&#9;UNIQUE</xsl:text>
				<xsl:if test="current()[@name]">
					<xsl:text> `</xsl:text>
					<xsl:value-of select="@name" />
					<xsl:text>`</xsl:text>
				</xsl:if>
				<xsl:text> (</xsl:text>
				<xsl:for-each select="./unique-column">
					<xsl:if test="position() != 1">
						<xsl:text>,</xsl:text>
					</xsl:if>
					<xsl:value-of select="@name" />
					<xsl:if test="current()[@size]">
						<xsl:text>(</xsl:text>
						<xsl:value-of select="@size" />
						<xsl:text>)</xsl:text>
					</xsl:if>
				</xsl:for-each>
				<xsl:text>)</xsl:text>
			</xsl:for-each>

			<xsl:text>&#10;) DEFAULT CHARSET=utf8;&#10;</xsl:text>
		</xsl:for-each>
	</xsl:template>
</xsl:stylesheet>

