/*
 * GTATECH - leitor RFID ESP32
 * ESP32 + MFRC522 + LCD I2C 20x4
 *
 * Identificacao mDNS: rfid.local
 *
 * API:
 * http://servertecnico.local/index.php?r=api-rfid
 *
 * Campo enviado: tag_uid=uid-em-hex-minusculo
 * Resposta esperada: l1|l2|l3|l4
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClient.h>
#include <Wire.h>
#include <SPI.h>
#include <MFRC522.h>
#include <LiquidCrystal_I2C.h>
#include <ESPmDNS.h>

// ========================================
// CONFIGURACAO WI-FI
// ========================================

const char* WIFI_SSID = "MundoSENAI";
const char* WIFI_PASS = "senai2025";

// Servidor PHP — rota registrada em config/routes.php
const char* SERVER_URL =
  "http://servertecnico.local/index.php?r=api-rfid";

// Nome mDNS do ESP32
const char* MDNS_HOSTNAME = "rfid";

// ========================================
// PINOS
// ========================================

#define RFID_SS_PIN   21
#define RFID_RST_PIN  22
#define LCD_SDA_PIN   33
#define LCD_SCL_PIN   25
#define LED_PIN       32

// ========================================
// OBJETOS
// ========================================

LiquidCrystal_I2C lcd(0x27, 20, 4);
MFRC522 rfid(RFID_SS_PIN, RFID_RST_PIN);

// ========================================
// VARIAVEIS GLOBAIS
// ========================================

unsigned long wifiRetryAt = 0;
unsigned long cardResetAt = 0;

String lastUid = "";

bool mdnsStarted = false;

// ========================================
// LCD
// ========================================

void lcdText(byte line, const String& text) {
  String padded = text.substring(0, 20);

  while (padded.length() < 20) {
    padded += ' ';
  }

  lcd.setCursor(0, line);
  lcd.print(padded);
}

void showLCD(
  const String& l1,
  const String& l2,
  const String& l3,
  const String& l4
) {
  lcdText(0, l1);
  lcdText(1, l2);
  lcdText(2, l3);
  lcdText(3, l4);
}

void waitingScreen() {
  showLCD(
    "GTATECH RFID",
    "Aproxime a TAG",
    "",
    ""
  );
}

// ========================================
// ESPmDNS
// ========================================

void startMDNS() {

  if (mdnsStarted) {
    return;
  }

  if (WiFi.status() != WL_CONNECTED) {
    return;
  }

  if (MDNS.begin(MDNS_HOSTNAME)) {

    mdnsStarted = true;

    Serial.println();
    Serial.println("================================");
    Serial.println("mDNS iniciado com sucesso!");
    Serial.print("Nome: ");
    Serial.println(MDNS_HOSTNAME);
    Serial.println("Endereco: http://rfid.local");
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());
    Serial.println("================================");

  } else {

    Serial.println("Falha ao iniciar mDNS");

  }
}

void stopMDNS() {

  if (mdnsStarted) {

    MDNS.end();
    mdnsStarted = false;

    Serial.println("mDNS encerrado");

  }
}

// ========================================
// UID DO CARTAO RFID
// ========================================

String uidFromCard() {

  String uid = "";

  uid.reserve(rfid.uid.size * 2);

  for (byte i = 0; i < rfid.uid.size; i++) {

    if (rfid.uid.uidByte[i] < 0x10) {
      uid += '0';
    }

    uid += String(rfid.uid.uidByte[i], HEX);
  }

  uid.toLowerCase();

  return uid;
}

// ========================================
// CONEXAO WI-FI
// ========================================

bool connectWiFi(bool showStatus) {

  if (WiFi.status() == WL_CONNECTED) {
    return true;
  }

  WiFi.mode(WIFI_STA);

  WiFi.begin(WIFI_SSID, WIFI_PASS);

  if (!showStatus) {
    return false;
  }

  showLCD(
    "GTATECH RFID",
    "Conectando Wi-Fi...",
    "Aguarde",
    ""
  );

  unsigned long start = millis();

  while (
    WiFi.status() != WL_CONNECTED &&
    millis() - start < 30000
  ) {

    delay(500);
    Serial.print('.');

  }

  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {

    Serial.print("Wi-Fi conectado. IP: ");
    Serial.println(WiFi.localIP());

    startMDNS();

    showLCD(
      "GTATECH RFID",
      "Wi-Fi conectado",
      "rfid.local",
      "Aproxime a TAG"
    );

    return true;

  }

  Serial.println("Falha ao conectar ao Wi-Fi");

  showLCD(
    "GTATECH RFID",
    "Falha no Wi-Fi",
    "Verifique rede/senha",
    "Tentando novamente"
  );

  return false;
}

// ========================================
// ENVIO DA TAG PARA O SERVIDOR
// ========================================

void sendTag(const String& uid) {

  if (WiFi.status() != WL_CONNECTED) {

    showLCD(
      "ERRO WI-FI",
      "Sem conexao",
      "Reconectando...",
      ""
    );

    wifiRetryAt = millis();

    WiFi.disconnect();

    connectWiFi(false);

    return;
  }

  showLCD(
    "GTATECH RFID",
    "Consultando servidor",
    "",
    ""
  );

  WiFiClient client;

  HTTPClient http;

  http.setTimeout(8000);

  if (!http.begin(client, SERVER_URL)) {

    showLCD(
      "ERRO HTTP",
      "Falha ao iniciar",
      "Tente novamente",
      ""
    );

    return;
  }

  http.addHeader(
    "Content-Type",
    "application/x-www-form-urlencoded"
  );

  String body = "tag_uid=" + uid;

  Serial.print("Enviando UID: ");
  Serial.println(uid);

  int httpCode = http.POST(body);

  Serial.print("Codigo HTTP: ");
  Serial.println(httpCode);

  if (httpCode <= 0) {

    Serial.print("Erro detalhado: ");
    Serial.println(http.errorToString(httpCode));

    Serial.print("IP do ESP32: ");
    Serial.println(WiFi.localIP());

    Serial.print("Servidor: ");
    Serial.println(SERVER_URL);

    showLCD(
      "ERRO DE CONEXAO",
      "Tente novamente",
      "",
      ""
    );

    http.end();

    return;
  }

  String response = http.getString();

  http.end();

  response.trim();

  Serial.println("Resposta do servidor:");
  Serial.println(response);

  if (
    httpCode < 200 ||
    httpCode >= 300 ||
    response.length() == 0
  ) {

    showLCD(
      "ERRO NO SERVIDOR",
      "Tente novamente",
      "",
      ""
    );

    return;
  }

  int p1 = response.indexOf('|');

  int p2 = p1 >= 0
    ? response.indexOf('|', p1 + 1)
    : -1;

  int p3 = p2 >= 0
    ? response.indexOf('|', p2 + 1)
    : -1;

  String l1 = p1 >= 0
    ? response.substring(0, p1)
    : "TAG lida";

  String l2 = p2 >= 0
    ? response.substring(p1 + 1, p2)
    : "Dados recebidos";

  String l3 = p3 >= 0
    ? response.substring(p2 + 1, p3)
    : "";

  String l4 = p3 >= 0
    ? response.substring(p3 + 1)
    : "";

  // ========================================
  // TITULO DO LCD CONFORME O RESULTADO
  // ========================================

  if (
    l2.indexOf("TAG desconhecida") >= 0 ||
    l4.indexOf("NEGADO") >= 0
  ) {

    showLCD(
      "TAG nao encontrada",
      l2,
      l3,
      l4
    );

  } else if (l3.startsWith("GTA:")) {

    showLCD(
      "GTA GTATECH",
      l2,
      l3,
      l4
    );

  } else if (
    l3.indexOf("SEM GTA") >= 0 ||
    l2.indexOf("SEM GTA") >= 0
  ) {

    showLCD(
      "SEM GTA ATIVA",
      l2,
      l3,
      l4
    );

  } else {

    showLCD(
      "Motorista localizado",
      l2,
      l3,
      l4
    );

  }
}

// ========================================
// SETUP
// ========================================

void setup() {

  Serial.begin(115200);

  delay(100);

  pinMode(LED_PIN, OUTPUT);

  digitalWrite(LED_PIN, LOW);

  Wire.begin(
    LCD_SDA_PIN,
    LCD_SCL_PIN
  );

  SPI.begin();

  lcd.init();

  lcd.backlight();

  waitingScreen();

  rfid.PCD_Init();

  Serial.print("Versao MFRC522: 0x");

  Serial.println(
    rfid.PCD_ReadRegister(rfid.VersionReg),
    HEX
  );

  connectWiFi(true);

}

// ========================================
// LOOP PRINCIPAL
// ========================================

void loop() {

  // Verifica conexao Wi-Fi

  if (WiFi.status() != WL_CONNECTED) {

    if (mdnsStarted) {
      stopMDNS();
    }

    if (
      wifiRetryAt == 0 ||
      millis() - wifiRetryAt >= 15000
    ) {

      wifiRetryAt = millis();

      Serial.println("Reconectando Wi-Fi...");

      WiFi.disconnect();

      WiFi.begin(
        WIFI_SSID,
        WIFI_PASS
      );

    }

  } else {

    // Inicia mDNS apos reconectar

    wifiRetryAt = 0;

    if (!mdnsStarted) {
      startMDNS();
    }

  }

  // Verifica nova TAG

  if (!rfid.PICC_IsNewCardPresent()) {
    return;
  }

  if (!rfid.PICC_ReadCardSerial()) {
    return;
  }

  String uid = uidFromCard();

  // Evita leitura duplicada

  if (
    uid == lastUid &&
    millis() - cardResetAt < 4000
  ) {

    rfid.PICC_HaltA();

    rfid.PCD_StopCrypto1();

    return;
  }

  lastUid = uid;

  cardResetAt = millis();

  digitalWrite(LED_PIN, HIGH);

  Serial.println("TAG lida: " + uid);

  sendTag(uid);

  rfid.PICC_HaltA();

  rfid.PCD_StopCrypto1();

  delay(4000);

  digitalWrite(LED_PIN, LOW);

  waitingScreen();

  lastUid = "";

  delay(500);

}