# TRA Integration for Node by [Alpha Olomi](https://github.com/alpha-olomi)

The **TRA SDK for Node.js** makes it easy for developers to access [TRA API](#) in their Javascript code, and build robust applications.

!!! warn  ""
    🚧 Work in progress, Use with caution


This package implements TRA (Tanzania Revenue Authority) APIs currently supporting VFD API only

## 🎉 Features

- VFD API
- TypeScript support
- Latest Node.js support
- Data Validation

## 🚀 Usage

### ⬇️ Installation

#### via [npm](https://www.npmjs.com/)

```bash
npm install node-tra-sdk
```

#### via [Yarn](https://yarnpkg.com/)

```bash
yarn add node-tra-sdk
```

### 🔶 Load key certificate

Before calling any APIs, you will need to load your key certificate. You can use this helper function to do so.

```javascript
import { loadKeyCertificate } from 'node-tra-sdk';

const { key } = await loadKeyCertificate('YOUR_KEY_FILE PATH', 'YOUR_KEY_PASSWORD');
```

### ℹ️ Registration

To get details about your company you need to call registration API. You call this once. You can reuse the result in the next steps.

```javascript
import { sendRegistrationRequest } from 'node-tra-sdk';

//test environment details
const hostname = 'virtual.tra.go.tz';
const path = '/efdmsRctApi/api/vfdRegReq';

const response = await sendRegistrationRequest({
  tin: 'YOUR TIN',
  certKey: 'YOUR CERT KEY',
  signKey: key, // key loaded from first step
  certSerial: 'YOUR CERT SERIAL',
  hostname: hostname,
  path: path,
});

//if successful, response.success == true
const { success, data } = response;
```

### 🔐 Token

To upload receipts/invoices you will need to provide a token to TRA API. To get the token call this helper function.

```javascript
import { sendTokenRequest } from 'node-tra-sdk';

//test environment details
const hostname = 'virtual.tra.go.tz';
const path = '/efdmsRctApi/vfdtoken';

const response = await sendTokenRequest({
  username: 'USERNAME_FROM_REGISTRATION_API',
  password: 'PASSWORD_FROM_REGISTRATION_API',
  grantType: 'password',
  hostname: hostname,
  path: path,
});

// if successful, response.success == true
const { success, data } = response;
```

### ⬆️ Upload invoice/receipt

```javascript
import { sendUploadInvoiceRequest } from 'node-tra-sdk';

//test environment details
const hostname = 'virtual.tra.go.tz';
const path = '/efdmsRctApi/api/efdmsRctInfo';

const response = await sendUploadInvoiceRequest({
  tin: 'YOUR TIN',
  signKey: key, // key loaded from first step
  certSerial: 'YOUR CERT SERIAL',
  token: 'YOUR TOKEN',
  routingKey: 'ROUTING KEY FROM REGISTRATION API',
  hostname: hostname,
  path: path,
  date: '2021-02-03',
  time: '20:52:53',
  regId: 'REGID_FROM_REGISTRATION_API',
  efdSerial: 'EFDSERIAL_FROM_REGISTRATION_API',
  receiptCode: 'RECEIPTCODE_FROM_REGISTRATION_API',
  rctNum: '10103',
  zNum: '20210203',
  dc: '1',
  gc: '10103',
  customerId: '',
  customerIdType: '6',
  customerName: 'John Doe',
  mobileNumber: '255755123123',
  items: [
    {
      ID: 1,
      DESC: 'Product 1',
      QTY: 1,
      TAXCODE: 1,
      AMT: '118000.00',
    },
  ],
  totals: {
    TOTALTAXEXCL: '100000.00',
    TOTALTAXINCL: '118000.00',
    DISCOUNT: '0.00',
  },
  payments: {
    PMTTYPE: 'EMONEY',
    PMTAMOUNT: '118000.00',
  },
  vatTotals: {
    VATRATE: 'A',
    NETTAMOUNT: '100000.00',
    TAXAMOUNT: '18000.00',
  },
});

// If successful, response.success == true
const { success, data } = response;
```

## 📚 References

- [TRA API](https://tra-docs.netlify.app/)
- Alternative [https://github.com/husseinmkwizu/node-tra](https://github.com/husseinmkwizu/node-tra)

## 📝 License

This project is licensed under the [MIT License](./LICENSE).

## 🙌 Credits

- [Alpha Olomi](https://github.com/alpha-olomi)
- [Emilian Ngatuma](https://github.com/punisher-n)
- [Hussein Mkwizu](https://github.com/husseinmkwizu)
- [Contributors](https://github.com/tra-developers/node-tra-sdk/graphs/contributors)
