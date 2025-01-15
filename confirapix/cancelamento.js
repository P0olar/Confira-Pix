const accessToken = '$aact_YTU5YTE0M2M2N2I4MTliNzk0YTI5N2U5MzdjNWZmNDQ6OjAwMDAwMDAwMDAwMDA1MjYxNzc6OiRhYWNoXzcxOWQyNTU0LTkwYWMtNGZmNi1iNDMyLTU1N2Q2ZWQ2NmZmZQ==';
const id = 'pay_rtmz1agdywhkuh48'

const url = `https://api.asaas.com//v3/payments/${id}`;
const options = {
    method: 'DELETE',
    headers: {
        accept: 'application/json',
        access_token: accessToken
    }
};

fetch(url, options)
    .then(res => res.json())
    .then(json => console.log(json))
    .catch(err => console.error(err));