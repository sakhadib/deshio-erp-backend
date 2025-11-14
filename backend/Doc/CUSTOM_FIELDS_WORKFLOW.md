# Custom Fields System - Frontend Integration Guide
### Complete Workflow for Dynamic Product & Service Fields

---

## 📋 Table of Contents
1. [System Overview](#system-overview)
2. [Field Types Reference](#field-types-reference)
3. [Workflow 1: Field Management](#workflow-1-field-management)
4. [Workflow 2: Product Creation with Fields](#workflow-2-product-creation-with-fields)
5. [Workflow 3: Service Creation with Fields](#workflow-3-service-creation-with-fields)
6. [UI Component Examples](#ui-component-examples)
7. [Complete Integration Examples](#complete-integration-examples)
8. [Best Practices](#best-practices)

---

## System Overview

### What are Custom Fields?
Custom fields allow you to add dynamic, user-defined attributes to products and services beyond the standard fields. Perfect for fashion ERP where products need specific attributes like:
- Fabric type, GSM, thread count
- Size, color, fit type
- Care instructions, certifications
- Designer name, collection, season

### Key Concepts
- **Field Definition**: The template/schema (created once)
- **Field Value**: The actual data (stored per product/service)
- **Field Types**: 16 types from text to file upload
- **Reusable**: One field definition used across multiple products

### Architecture
```
Fields Table (Definition)
    ↓
Product_Fields Pivot (Values)
    ↓
Products (Uses fields)
```

---

## Field Types Reference

### All Available Types

| Type | Input Control | Options Required | Use Case |
|------|---------------|------------------|----------|
| `text` | Single line input | No | Brand name, SKU, model number |
| `textarea` | Multi-line input | No | Description, care instructions |
| `number` | Numeric input | No | Weight, dimensions, quantity |
| `email` | Email input | No | Designer email, contact |
| `url` | URL input | No | Product page, lookbook link |
| `tel` | Phone input | No | Supplier phone |
| `date` | Date picker | No | Manufacturing date, launch date |
| `datetime` | Date+Time picker | No | Event timestamp |
| `time` | Time picker | No | Production time |
| `select` | Dropdown | **Yes** | Fabric type, size, category |
| `radio` | Radio buttons | **Yes** | Gender (M/F), availability |
| `checkbox` | Multiple checkboxes | **Yes** | Features, certifications |
| `file` | File upload | No | PDF specs, certificates |
| `image` | Image upload | No | Swatch, pattern image |
| `color` | Color picker | No | Actual color value |
| `range` | Slider | No | Quality rating (1-10) |

### Field Properties

```typescript
interface Field {
  id: number;
  title: string;                    // Display name
  type: FieldType;                  // One of 16 types above
  description: string | null;       // Helper text
  is_required: boolean;             // Validation
  default_value: string | null;     // Pre-filled value
  options: string[] | null;         // For select/radio/checkbox
  validation_rules: string | null;  // Laravel rules (e.g., "min:3|max:100")
  placeholder: string | null;       // Input placeholder
  order: number;                    // Display order
  is_active: boolean;               // Show/hide
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}
```

---

## Workflow 1: Field Management

### Step 1.1: Get Available Field Types

**Purpose**: Show field type options when admin creates a new field

**Endpoint**: `GET /api/fields/types`

**Request**:
```javascript
const response = await fetch('/api/fields/types', {
  headers: {
    'Authorization': `Bearer ${employeeToken}`,
    'Accept': 'application/json'
  }
});

const { data: types } = await response.json();
```

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "value": "text",
      "label": "Text",
      "description": "Single line text input",
      "supports_options": false
    },
    {
      "value": "select",
      "label": "Select Dropdown",
      "description": "Dropdown selection",
      "supports_options": true
    },
    // ... 14 more types
  ]
}
```

**UI Usage**:
```jsx
// React example
const [fieldTypes, setFieldTypes] = useState([]);

useEffect(() => {
  fetchFieldTypes().then(types => setFieldTypes(types));
}, []);

return (
  <select name="type" onChange={handleTypeChange}>
    {fieldTypes.map(type => (
      <option key={type.value} value={type.value}>
        {type.label} - {type.description}
      </option>
    ))}
  </select>
);

// Show options input only if supports_options is true
{selectedType?.supports_options && (
  <OptionsInput 
    placeholder="Enter options separated by comma"
  />
)}
```

---

### Step 1.2: Create a New Field

**Purpose**: Admin creates reusable field definitions

**Endpoint**: `POST /api/fields`

**Request Body**:
```json
{
  "title": "Fabric Type",
  "type": "select",
  "description": "Type of fabric material used",
  "is_required": true,
  "options": [
    "Cotton",
    "Silk",
    "Polyester",
    "Linen",
    "Wool",
    "Viscose",
    "Georgette"
  ],
  "placeholder": "Select fabric type",
  "is_active": true
}
```

**More Examples**:

```javascript
// Text field
{
  "title": "Designer Name",
  "type": "text",
  "description": "Name of the designer",
  "is_required": false,
  "placeholder": "e.g., John Doe",
  "is_active": true
}

// Number field
{
  "title": "GSM (Fabric Weight)",
  "type": "number",
  "description": "Grams per square meter",
  "is_required": true,
  "validation_rules": "numeric|min:50|max:500",
  "placeholder": "e.g., 180",
  "is_active": true
}

// Date field
{
  "title": "Collection Launch Date",
  "type": "date",
  "description": "When this collection launches",
  "is_required": false,
  "is_active": true
}

// Checkbox field (multiple selection)
{
  "title": "Certifications",
  "type": "checkbox",
  "description": "Quality certifications",
  "is_required": false,
  "options": [
    "GOTS Certified",
    "Oeko-Tex Standard 100",
    "Fair Trade",
    "Organic Cotton",
    "BCI Cotton"
  ],
  "is_active": true
}
```

**Response**:
```json
{
  "success": true,
  "message": "Field created successfully",
  "data": {
    "id": 15,
    "title": "Fabric Type",
    "type": "select",
    "description": "Type of fabric material used",
    "is_required": true,
    "options": ["Cotton", "Silk", "Polyester", "Linen", "Wool", "Viscose", "Georgette"],
    "placeholder": "Select fabric type",
    "order": 15,
    "is_active": true,
    "created_at": "2025-11-14T10:00:00.000000Z"
  }
}
```

---

### Step 1.3: Get All Active Fields

**Purpose**: Fetch fields to show in product/service creation form

**Endpoint**: `GET /api/fields/active`

**Request**:
```javascript
const response = await fetch('/api/fields/active', {
  headers: {
    'Authorization': `Bearer ${employeeToken}`,
    'Accept': 'application/json'
  }
});

const { data: activeFields } = await response.json();
```

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Fabric Type",
      "type": "select",
      "description": "Type of fabric material used",
      "is_required": true,
      "options": ["Cotton", "Silk", "Polyester", "Linen", "Wool"],
      "placeholder": "Select fabric type",
      "order": 1,
      "is_active": true
    },
    {
      "id": 2,
      "title": "GSM (Fabric Weight)",
      "type": "number",
      "description": "Grams per square meter",
      "is_required": true,
      "validation_rules": "numeric|min:50|max:500",
      "placeholder": "e.g., 180",
      "order": 2,
      "is_active": true
    },
    {
      "id": 3,
      "title": "Designer Name",
      "type": "text",
      "description": "Name of the designer",
      "is_required": false,
      "placeholder": "e.g., John Doe",
      "order": 3,
      "is_active": true
    },
    {
      "id": 4,
      "title": "Care Instructions",
      "type": "textarea",
      "description": "How to care for this item",
      "is_required": true,
      "placeholder": "Machine wash cold, tumble dry low...",
      "order": 4,
      "is_active": true
    }
  ]
}
```

---

### Step 1.4: List All Fields (with Filters)

**Purpose**: Admin field management page with search/filter

**Endpoint**: `GET /api/fields`

**Query Parameters**:
- `type` - Filter by field type (text, select, etc.)
- `is_active` - Filter by active status (true/false)
- `is_required` - Filter by required status (true/false)
- `per_page` - Pagination (default: 50)

**Examples**:
```javascript
// Get all fields with pagination
GET /api/fields?per_page=20

// Get only active select fields
GET /api/fields?type=select&is_active=true

// Get all required fields
GET /api/fields?is_required=true
```

---

## Workflow 2: Product Creation with Fields

### Step 2.1: Fetch Active Fields

**Before showing product creation form**, fetch all active fields:

```javascript
async function loadProductForm() {
  // Get active fields
  const fieldsResponse = await fetch('/api/fields/active', {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const { data: customFields } = await fieldsResponse.json();
  
  // Store in state
  setCustomFields(customFields);
}
```

---

### Step 2.2: Render Dynamic Fields in Form

**UI Component Pattern**:

```jsx
function ProductForm() {
  const [customFields, setCustomFields] = useState([]);
  const [fieldValues, setFieldValues] = useState({});

  // Render field based on type
  const renderField = (field) => {
    const commonProps = {
      id: `field_${field.id}`,
      name: `custom_fields[${field.id}]`,
      required: field.is_required,
      placeholder: field.placeholder,
      onChange: (e) => handleFieldChange(field.id, e.target.value)
    };

    switch (field.type) {
      case 'text':
      case 'email':
      case 'url':
      case 'tel':
        return <input type={field.type} {...commonProps} />;
      
      case 'number':
        return <input type="number" {...commonProps} />;
      
      case 'textarea':
        return <textarea {...commonProps} rows="4" />;
      
      case 'date':
      case 'datetime-local':
      case 'time':
        return <input type={field.type} {...commonProps} />;
      
      case 'select':
        return (
          <select {...commonProps}>
            <option value="">-- Select --</option>
            {field.options.map(opt => (
              <option key={opt} value={opt}>{opt}</option>
            ))}
          </select>
        );
      
      case 'radio':
        return (
          <div className="radio-group">
            {field.options.map(opt => (
              <label key={opt}>
                <input 
                  type="radio" 
                  name={`custom_fields[${field.id}]`}
                  value={opt}
                  required={field.is_required}
                  onChange={(e) => handleFieldChange(field.id, e.target.value)}
                />
                {opt}
              </label>
            ))}
          </div>
        );
      
      case 'checkbox':
        return (
          <div className="checkbox-group">
            {field.options.map(opt => (
              <label key={opt}>
                <input 
                  type="checkbox" 
                  name={`custom_fields[${field.id}][]`}
                  value={opt}
                  onChange={(e) => handleCheckboxChange(field.id, opt, e.target.checked)}
                />
                {opt}
              </label>
            ))}
          </div>
        );
      
      case 'color':
        return <input type="color" {...commonProps} />;
      
      case 'file':
      case 'image':
        return <input type="file" {...commonProps} />;
      
      case 'range':
        return (
          <div>
            <input type="range" {...commonProps} />
            <span>{fieldValues[field.id] || field.default_value || 0}</span>
          </div>
        );
      
      default:
        return <input type="text" {...commonProps} />;
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      {/* Standard product fields */}
      <input name="name" placeholder="Product Name" required />
      <input name="sku" placeholder="SKU" required />
      <input name="price" type="number" placeholder="Price" required />
      
      {/* Dynamic custom fields section */}
      <fieldset>
        <legend>Additional Details</legend>
        {customFields.map(field => (
          <div key={field.id} className="form-group">
            <label htmlFor={`field_${field.id}`}>
              {field.title}
              {field.is_required && <span className="required">*</span>}
            </label>
            {field.description && (
              <small className="help-text">{field.description}</small>
            )}
            {renderField(field)}
          </div>
        ))}
      </fieldset>
      
      <button type="submit">Create Product</button>
    </form>
  );
}
```

---

### Step 2.3: Submit Product with Custom Field Values

**Endpoint**: `POST /api/products`

**Request Body Structure**:
```json
{
  "name": "Premium Jamdani Saree",
  "sku": "JAM-2025-001",
  "category_id": 5,
  "price": 8500.00,
  "cost": 5000.00,
  "description": "Handwoven Jamdani saree with intricate patterns",
  
  "custom_fields": {
    "1": "Cotton",
    "2": "180",
    "3": "Kamal Ahmed",
    "4": "Hand wash only. Do not bleach. Dry in shade.",
    "5": "2025-12-01",
    "6": ["GOTS Certified", "Fair Trade"]
  }
}
```

**Field Value Format by Type**:

| Type | Value Format | Example |
|------|--------------|---------|
| text, textarea, email, url, tel | String | `"John Doe"` |
| number | String (numeric) | `"180"` |
| date, datetime, time | ISO Date String | `"2025-12-01"` |
| select, radio | String (option value) | `"Cotton"` |
| checkbox | Array of strings | `["Option1", "Option2"]` |
| color | Hex color | `"#FF5733"` |
| file, image | File path (after upload) | `"uploads/cert.pdf"` |
| range | String (numeric) | `"7"` |

**Complete Example**:

```javascript
async function createProduct(formData) {
  const productData = {
    // Standard fields
    name: formData.name,
    sku: formData.sku,
    category_id: formData.category_id,
    price: parseFloat(formData.price),
    cost: parseFloat(formData.cost),
    description: formData.description,
    
    // Custom fields
    custom_fields: {}
  };
  
  // Add custom field values
  customFields.forEach(field => {
    const value = fieldValues[field.id];
    
    if (field.type === 'checkbox') {
      // Checkbox returns array
      productData.custom_fields[field.id] = value || [];
    } else if (field.type === 'number' || field.type === 'range') {
      // Keep as string (API handles conversion)
      productData.custom_fields[field.id] = value?.toString() || '';
    } else {
      // Everything else as string
      productData.custom_fields[field.id] = value || '';
    }
  });
  
  const response = await fetch('/api/products', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(productData)
  });
  
  const result = await response.json();
  return result;
}
```

**Response**:
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 150,
    "name": "Premium Jamdani Saree",
    "sku": "JAM-2025-001",
    "price": 8500.00,
    "custom_fields": [
      {
        "field_id": 1,
        "field_title": "Fabric Type",
        "field_type": "select",
        "value": "Cotton"
      },
      {
        "field_id": 2,
        "field_title": "GSM (Fabric Weight)",
        "field_type": "number",
        "value": "180"
      },
      {
        "field_id": 3,
        "field_title": "Designer Name",
        "field_type": "text",
        "value": "Kamal Ahmed"
      },
      {
        "field_id": 4,
        "field_title": "Care Instructions",
        "field_type": "textarea",
        "value": "Hand wash only. Do not bleach. Dry in shade."
      }
    ]
  }
}
```

---

### Step 2.4: View Product with Custom Fields

**Endpoint**: `GET /api/products/{id}`

**Response** (includes custom fields):
```json
{
  "success": true,
  "data": {
    "id": 150,
    "name": "Premium Jamdani Saree",
    "sku": "JAM-2025-001",
    "price": 8500.00,
    "description": "Handwoven Jamdani saree...",
    
    "custom_fields": [
      {
        "field_id": 1,
        "field_title": "Fabric Type",
        "field_type": "select",
        "field_options": ["Cotton", "Silk", "Polyester"],
        "value": "Cotton"
      },
      {
        "field_id": 2,
        "field_title": "GSM (Fabric Weight)",
        "field_type": "number",
        "value": "180"
      }
    ]
  }
}
```

**Display Custom Fields**:

```jsx
function ProductDetails({ product }) {
  return (
    <div className="product-details">
      <h2>{product.name}</h2>
      <p>SKU: {product.sku}</p>
      <p>Price: ৳{product.price}</p>
      
      {product.custom_fields && product.custom_fields.length > 0 && (
        <div className="custom-fields">
          <h3>Additional Details</h3>
          <dl>
            {product.custom_fields.map(field => (
              <div key={field.field_id}>
                <dt>{field.field_title}</dt>
                <dd>{formatFieldValue(field)}</dd>
              </div>
            ))}
          </dl>
        </div>
      )}
    </div>
  );
}

function formatFieldValue(field) {
  if (field.field_type === 'checkbox' && Array.isArray(field.value)) {
    return field.value.join(', ');
  }
  if (field.field_type === 'date') {
    return new Date(field.value).toLocaleDateString();
  }
  if (field.field_type === 'url') {
    return <a href={field.value} target="_blank">{field.value}</a>;
  }
  return field.value;
}
```

---

### Step 2.5: Update Product Custom Fields

**Endpoint**: `POST /api/products/{id}/custom-fields`

**Update Single Field**:
```json
{
  "field_id": 1,
  "value": "Silk"
}
```

**Update Multiple Fields** (use PUT /api/products/{id}):
```json
{
  "custom_fields": {
    "1": "Silk",
    "2": "220",
    "3": "New Designer Name"
  }
}
```

---

## Workflow 3: Service Creation with Fields

Services use the **same field system** as products with one difference: services can have additional metadata.

### Step 3.1: Create Service with Custom Fields

**Endpoint**: `POST /api/services`

```json
{
  "name": "Premium Tailoring",
  "category": "tailoring",
  "base_price": 1500.00,
  "description": "Expert tailoring service",
  "duration_minutes": 120,
  
  "custom_fields": {
    "10": "Standard",
    "11": "3-5 business days",
    "12": "Machine and hand stitching"
  }
}
```

---

## UI Component Examples

### Complete Product Form Component

```jsx
import React, { useState, useEffect } from 'react';

function ProductCreateForm() {
  const [customFields, setCustomFields] = useState([]);
  const [fieldValues, setFieldValues] = useState({});
  const [loading, setLoading] = useState(false);
  
  // Load custom fields on mount
  useEffect(() => {
    loadCustomFields();
  }, []);
  
  async function loadCustomFields() {
    try {
      const response = await fetch('/api/fields/active', {
        headers: { 'Authorization': `Bearer ${getToken()}` }
      });
      const { data } = await response.json();
      setCustomFields(data);
      
      // Initialize field values with defaults
      const initialValues = {};
      data.forEach(field => {
        if (field.default_value) {
          initialValues[field.id] = field.default_value;
        }
      });
      setFieldValues(initialValues);
    } catch (error) {
      console.error('Failed to load custom fields:', error);
    }
  }
  
  function handleFieldChange(fieldId, value) {
    setFieldValues(prev => ({
      ...prev,
      [fieldId]: value
    }));
  }
  
  function handleCheckboxChange(fieldId, option, checked) {
    setFieldValues(prev => {
      const current = prev[fieldId] || [];
      if (checked) {
        return { ...prev, [fieldId]: [...current, option] };
      } else {
        return { ...prev, [fieldId]: current.filter(o => o !== option) };
      }
    });
  }
  
  async function handleSubmit(e) {
    e.preventDefault();
    setLoading(true);
    
    const formData = new FormData(e.target);
    const productData = {
      name: formData.get('name'),
      sku: formData.get('sku'),
      category_id: parseInt(formData.get('category_id')),
      price: parseFloat(formData.get('price')),
      cost: parseFloat(formData.get('cost')),
      description: formData.get('description'),
      custom_fields: fieldValues
    };
    
    try {
      const response = await fetch('/api/products', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${getToken()}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(productData)
      });
      
      const result = await response.json();
      if (result.success) {
        alert('Product created successfully!');
        // Redirect or reset form
      } else {
        alert('Error: ' + result.message);
      }
    } catch (error) {
      alert('Failed to create product');
    } finally {
      setLoading(false);
    }
  }
  
  function renderCustomField(field) {
    const value = fieldValues[field.id] || '';
    
    switch (field.type) {
      case 'select':
        return (
          <select 
            value={value}
            onChange={(e) => handleFieldChange(field.id, e.target.value)}
            required={field.is_required}
          >
            <option value="">-- Select {field.title} --</option>
            {field.options?.map(opt => (
              <option key={opt} value={opt}>{opt}</option>
            ))}
          </select>
        );
      
      case 'checkbox':
        return (
          <div className="checkbox-group">
            {field.options?.map(opt => (
              <label key={opt} className="checkbox-label">
                <input 
                  type="checkbox"
                  checked={(value || []).includes(opt)}
                  onChange={(e) => handleCheckboxChange(field.id, opt, e.target.checked)}
                />
                {opt}
              </label>
            ))}
          </div>
        );
      
      case 'textarea':
        return (
          <textarea
            value={value}
            onChange={(e) => handleFieldChange(field.id, e.target.value)}
            placeholder={field.placeholder}
            required={field.is_required}
            rows="4"
          />
        );
      
      case 'number':
        return (
          <input
            type="number"
            value={value}
            onChange={(e) => handleFieldChange(field.id, e.target.value)}
            placeholder={field.placeholder}
            required={field.is_required}
          />
        );
      
      case 'date':
        return (
          <input
            type="date"
            value={value}
            onChange={(e) => handleFieldChange(field.id, e.target.value)}
            required={field.is_required}
          />
        );
      
      default:
        return (
          <input
            type={field.type}
            value={value}
            onChange={(e) => handleFieldChange(field.id, e.target.value)}
            placeholder={field.placeholder}
            required={field.is_required}
          />
        );
    }
  }
  
  return (
    <form onSubmit={handleSubmit} className="product-form">
      <h2>Create New Product</h2>
      
      {/* Standard Fields */}
      <div className="form-section">
        <h3>Basic Information</h3>
        
        <div className="form-group">
          <label>Product Name *</label>
          <input name="name" required />
        </div>
        
        <div className="form-group">
          <label>SKU *</label>
          <input name="sku" required />
        </div>
        
        <div className="form-group">
          <label>Category *</label>
          <select name="category_id" required>
            <option value="">-- Select Category --</option>
            {/* Load categories dynamically */}
          </select>
        </div>
        
        <div className="form-row">
          <div className="form-group">
            <label>Price *</label>
            <input name="price" type="number" step="0.01" required />
          </div>
          
          <div className="form-group">
            <label>Cost *</label>
            <input name="cost" type="number" step="0.01" required />
          </div>
        </div>
        
        <div className="form-group">
          <label>Description</label>
          <textarea name="description" rows="4" />
        </div>
      </div>
      
      {/* Dynamic Custom Fields */}
      {customFields.length > 0 && (
        <div className="form-section">
          <h3>Additional Details</h3>
          
          {customFields.map(field => (
            <div key={field.id} className="form-group">
              <label>
                {field.title}
                {field.is_required && <span className="required">*</span>}
              </label>
              
              {field.description && (
                <small className="help-text">{field.description}</small>
              )}
              
              {renderCustomField(field)}
            </div>
          ))}
        </div>
      )}
      
      <div className="form-actions">
        <button type="button" onClick={() => window.history.back()}>
          Cancel
        </button>
        <button type="submit" disabled={loading}>
          {loading ? 'Creating...' : 'Create Product'}
        </button>
      </div>
    </form>
  );
}

export default ProductCreateForm;
```

---

## Complete Integration Examples

### Example 1: Fashion Product with Complete Fields

```javascript
// Step 1: Load fields for fashion category
const fashionFields = await fetch('/api/fields/active').then(r => r.json());

// Step 2: Render form with these fields
// Fields: Fabric Type, GSM, Designer, Care Instructions, Size, Color

// Step 3: User fills form
const productData = {
  name: "Handwoven Jamdani Saree",
  sku: "JAM-RED-001",
  category_id: 5,
  price: 12500.00,
  cost: 7500.00,
  description: "Exquisite red Jamdani with traditional motifs",
  
  custom_fields: {
    "1": "Cotton",              // Fabric Type (select)
    "2": "180",                 // GSM (number)
    "3": "Kamal Ahmed",         // Designer (text)
    "4": "Hand wash cold...",   // Care Instructions (textarea)
    "5": "Free Size",           // Size (select)
    "6": "Red",                 // Color (color picker)
    "7": ["GOTS", "Fair Trade"],// Certifications (checkbox)
    "8": "2025-12-15"          // Collection Launch (date)
  }
};

// Step 4: Submit
const response = await fetch('/api/products', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(productData)
});
```

---

### Example 2: Admin Field Management Page

```jsx
function FieldManagementPage() {
  const [fields, setFields] = useState([]);
  const [showCreateModal, setShowCreateModal] = useState(false);
  
  useEffect(() => {
    loadFields();
  }, []);
  
  async function loadFields() {
    const response = await fetch('/api/fields', {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    const { data } = await response.json();
    setFields(data.data); // Paginated response
  }
  
  async function toggleFieldStatus(fieldId, currentStatus) {
    const endpoint = currentStatus 
      ? `/api/fields/${fieldId}/deactivate`
      : `/api/fields/${fieldId}/activate`;
    
    await fetch(endpoint, {
      method: 'PATCH',
      headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    
    loadFields(); // Reload
  }
  
  async function deleteField(fieldId) {
    if (!confirm('Are you sure?')) return;
    
    await fetch(`/api/fields/${fieldId}`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    
    loadFields();
  }
  
  return (
    <div className="field-management">
      <div className="page-header">
        <h1>Custom Fields Management</h1>
        <button onClick={() => setShowCreateModal(true)}>
          + Create New Field
        </button>
      </div>
      
      <table className="fields-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Type</th>
            <th>Required</th>
            <th>Status</th>
            <th>Order</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {fields.map(field => (
            <tr key={field.id}>
              <td>{field.title}</td>
              <td>
                <span className="badge">{field.type}</span>
              </td>
              <td>
                {field.is_required ? '✓ Yes' : '✗ No'}
              </td>
              <td>
                <span className={`status ${field.is_active ? 'active' : 'inactive'}`}>
                  {field.is_active ? 'Active' : 'Inactive'}
                </span>
              </td>
              <td>{field.order}</td>
              <td className="actions">
                <button onClick={() => editField(field)}>Edit</button>
                <button onClick={() => toggleFieldStatus(field.id, field.is_active)}>
                  {field.is_active ? 'Deactivate' : 'Activate'}
                </button>
                <button onClick={() => deleteField(field.id)} className="danger">
                  Delete
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      
      {showCreateModal && (
        <FieldCreateModal 
          onClose={() => setShowCreateModal(false)}
          onSuccess={loadFields}
        />
      )}
    </div>
  );
}
```

---

## Best Practices

### 1. Field Validation
```javascript
// Client-side validation before submit
function validateCustomFields(fields, values) {
  const errors = {};
  
  fields.forEach(field => {
    const value = values[field.id];
    
    // Check required
    if (field.is_required && !value) {
      errors[field.id] = `${field.title} is required`;
    }
    
    // Type-specific validation
    if (field.type === 'email' && value && !isValidEmail(value)) {
      errors[field.id] = 'Invalid email format';
    }
    
    if (field.type === 'number' && value && isNaN(value)) {
      errors[field.id] = 'Must be a number';
    }
    
    // Custom validation rules
    if (field.validation_rules) {
      // Parse Laravel validation rules
      const rules = field.validation_rules.split('|');
      rules.forEach(rule => {
        if (rule.startsWith('min:')) {
          const min = parseInt(rule.split(':')[1]);
          if (value && value.length < min) {
            errors[field.id] = `Minimum ${min} characters`;
          }
        }
      });
    }
  });
  
  return errors;
}
```

### 2. Performance Optimization
```javascript
// Cache fields for 5 minutes
const FIELD_CACHE_KEY = 'custom_fields_cache';
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

async function getActiveFields() {
  const cached = localStorage.getItem(FIELD_CACHE_KEY);
  if (cached) {
    const { data, timestamp } = JSON.parse(cached);
    if (Date.now() - timestamp < CACHE_DURATION) {
      return data;
    }
  }
  
  const response = await fetch('/api/fields/active');
  const { data } = await response.json();
  
  localStorage.setItem(FIELD_CACHE_KEY, JSON.stringify({
    data,
    timestamp: Date.now()
  }));
  
  return data;
}
```

### 3. Error Handling
```javascript
async function createProductWithFields(data) {
  try {
    const response = await fetch('/api/products', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getToken()}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    });
    
    const result = await response.json();
    
    if (!response.ok) {
      // Handle validation errors
      if (result.errors) {
        // Show field-specific errors
        Object.keys(result.errors).forEach(key => {
          if (key.startsWith('custom_fields.')) {
            const fieldId = key.split('.')[1];
            showFieldError(fieldId, result.errors[key][0]);
          }
        });
      }
      throw new Error(result.message);
    }
    
    return result.data;
  } catch (error) {
    console.error('Product creation failed:', error);
    throw error;
  }
}
```

### 4. Field Dependencies
```javascript
// Show/hide fields based on other field values
function useFieldDependency(fields, values) {
  const [visibleFields, setVisibleFields] = useState(fields);
  
  useEffect(() => {
    const visible = fields.filter(field => {
      // Example: Show "Thread Count" only if "Fabric Type" is "Cotton"
      if (field.title === 'Thread Count') {
        return values[getFabricTypeFieldId()] === 'Cotton';
      }
      return true;
    });
    
    setVisibleFields(visible);
  }, [fields, values]);
  
  return visibleFields;
}
```

### 5. Bulk Operations
```javascript
// Activate multiple fields at once
async function bulkActivateFields(fieldIds) {
  const response = await fetch('/api/fields/bulk/status', {
    method: 'PATCH',
    headers: {
      'Authorization': `Bearer ${getToken()}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      field_ids: fieldIds,
      is_active: true
    })
  });
  
  return response.json();
}
```

---

## Quick Reference

### API Endpoints Summary

| Action | Method | Endpoint |
|--------|--------|----------|
| List fields | GET | `/api/fields` |
| Get active fields | GET | `/api/fields/active` |
| Get field types | GET | `/api/fields/types` |
| Create field | POST | `/api/fields` |
| Update field | PUT | `/api/fields/{id}` |
| Delete field | DELETE | `/api/fields/{id}` |
| Get statistics | GET | `/api/fields/statistics` |
| Create product | POST | `/api/products` |
| Update product field | POST | `/api/products/{id}/custom-fields` |

### Field Value Schema

```typescript
// In product creation/update
{
  custom_fields: {
    [fieldId: number]: string | string[] | number
  }
}

// Examples
custom_fields: {
  "1": "Cotton",                    // select
  "2": "180",                       // number
  "3": ["Certified", "Organic"],    // checkbox
  "4": "2025-12-01"                 // date
}
```

---

**Document Version**: 1.0  
**Last Updated**: November 14, 2025  
**Target Audience**: Frontend Developers  
**Related Docs**: PRODUCT_MANAGEMENT_SYSTEM.md, API Documentation
