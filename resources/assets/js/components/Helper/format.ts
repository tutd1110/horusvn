// format price vnd
export const formatPrice = (price: any) => {
    let x = price;
    x = x.toLocaleString("it-IT", { style: "currency", currency: "VND" });
    return x;
};